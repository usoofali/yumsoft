<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    public function getUpdates(Request $request, Shop $shop)
    {
        if (! $request->user()->canAccessShop($shop)) {
        return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'products' => 'nullable|date',
            'customers' => 'nullable|date',
            'stock' => 'nullable|date'
        ]);

        $response = [];

        // Products updated since last sync
    if ($request->has('products') && $request->products !== null) {
        $response['products'] = Product::where('updated_at', '>', $request->products)
            ->select(['id', 'name', 'barcode', 'price', 'cost_price'])
            ->get();
    } else {
        // If 'products' key is not present or its value is null, fetch all products
        $response['products'] = Product::select(['id', 'name', 'barcode', 'price', 'cost_price'])->get();
    }

    // Customers updated since last sync
    if ($request->has('customers') && $request->customers !== null) {
        $response['customers'] = Customer::where('shop_id', $shop->id)
            ->where('updated_at', '>', $request->customers)
            ->select(['id','shop_id', 'name', 'phone', 'email', 'address', 'credit_limit'])
            ->get();
    } else {
        // If 'customers' key is not present or its value is null, fetch all customers for the shop
        $response['customers'] = Customer::where('shop_id', $shop->id)
            ->select(['id','shop_id', 'name', 'phone', 'email', 'address', 'credit_limit'])
            ->get();
    }

    // Stock levels updated since last sync
    if ($request->has('stock') && $request->stock !== null) {
        $response['stock'] = Stock::where('shop_id', $shop->id)
            ->where('updated_at', '>', $request->stock)
            ->select(['product_id', 'quantity'])
            ->get();
    } else {
        // If 'stock' key is not present or its value is null, fetch all stock levels for the shop
        $response['stock'] = Stock::where('shop_id', $shop->id)
            ->select(['product_id', 'quantity'])
            ->get();
    }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function pushSales(Request $request)
    {
        $validated = $request->validate([
            'sales' => 'required|array',
            'sales.*.id' => 'required|integer',
            'sales.*.shop_id' => 'required|exists:shops,id',
            'sales.*.customer_id' => 'nullable|exists:customers,id',
            'sales.*.items' => 'required|array',
            'sales.*.items.*.product_id' => 'required|exists:products,id',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.total_price' => 'required|numeric|min:0'
        ]);

        $processed = [];
        
        foreach ($validated['sales'] as $saleData) {
            // Verify shop access for each sale
            if (!$request->user()->canAccessShop($saleData['shop_id'])) {
                continue;
            }

            $sale = Invoice::create([
                'shop_id' => $saleData['shop_id'],
                'customer_id' => $saleData['customer_id'] ?? null,
                'total_price' => $saleData['total_price'],
                'status' => 'unpaid',
                'created_at' => $saleData['created_at'] ?? now()
            ]);

            foreach ($saleData['items'] as $item) {
                InvoiceItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ]);
            }

            $processed[] = $sale->id;
        }

        return response()->json([
            'success' => true,
            'processed' => $processed
        ], 201);
    }

    public function pushCustomers(Request $request)
    {
        $validated = $request->validate([
            'customers' => 'required|array',
            'customers.*.id' => 'required|integer',
            'customers.*.shop_id' => 'required|exists:shops,id',
            'customers.*.name' => 'required|string',
            'customers.*.phone' => 'nullable|string'
        ]);

        $processed = [];
        
        foreach ($validated['customers'] as $customerData) {
            // Verify shop access for each customer
            if (!$request->user()->canAccessShop($customerData['shop_id'])) {
                continue;
            }

            $customer = Customer::create([
                'shop_id' => $customerData['shop_id'],
                'name' => $customerData['name'],
                'phone' => $customerData['phone'] ?? null,
                'address' => $customerData['address'] ?? null,
                'created_at' => $customerData['created_at'] ?? now()
            ]);

            $processed[] = $customer->id;
        }

        return response()->json([
            'success' => true,
            'processed' => $processed
        ], 201);
    }

    public function pushPayments(Request $request)
    {
        $validated = $request->validate([
            'payments' => 'required|array',
            'payments.*.sale_id' => 'required|exists:sales,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.method' => 'required|in:cash,card,transfer'
        ]);

        $processed = [];
        
        foreach ($validated['payments'] as $paymentData) {
            $sale = Invoice::find($paymentData['sale_id']);
            
            // Verify shop access for each payment
            if (!$request->user()->canAccessShop($sale->shop_id)) {
                continue;
            }

            $payment = Payment::create([
                'sale_id' => $paymentData['sale_id'],
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'],
                'created_at' => $paymentData['created_at'] ?? now()
            ]);

            // Update sale status
            $totalPaid = $sale->payments()->sum('amount');
            $status = ($totalPaid >= $sale->total_price) ? 'paid' : 'partially_paid';
            $sale->update(['status' => $status]);

            $processed[] = $payment->id;
        }

        return response()->json([
            'success' => true,
            'processed' => $processed
        ], 201);
    }
}