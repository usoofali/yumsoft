<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SaleController extends Controller
{
    public function store(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'sometimes|numeric|min:0',
            'items.*.discount_rate' => 'sometimes|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0'
        ]);

        // Verify shop access
        if (! $request->user()->canAccessShop($shop)) {
        return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function() use ($validated) {
            // Create sale
            $sale = Sale::create([
                'shop_id' => $validated['shop_id'],
                'customer_id' => $validated['customer_id'] ?? null,
                'total_price' => $validated['total_price'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'status' => 'unpaid'
            ]);

            // Add sale items
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount_rate' => $item['discount_rate'] ?? 0
                ]);

                // Update stock
                ShopStock::where('shop_id', $validated['shop_id'])
                    ->where('product_id', $item['product_id'])
                    ->decrement('quantity', $item['quantity']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number
                ]
            ], 201);
        });
    }

    public function uploadReceipt(Sale $sale, Request $request)
    {
        // Verify user has access to this sale's shop
        if (!$request->user()->canAccessShop($sale->shop_id)) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $path = $request->file('receipt')->store('receipts');

        $sale->update([
            'receipt_path' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receipt uploaded successfully'
        ]);
    }

    public function recordPayment(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:cash,card,transfer',
            'transaction_id' => 'nullable|string'
        ]);

        return DB::transaction(function() use ($validated, $request) {
            $sale = Sale::findOrFail($validated['sale_id']);
            
            // Verify shop access
            if (!$request->user()->canAccessShop($sale->shop_id)) {
                abort(403, 'Unauthorized access');
            }

            // Create payment
            $payment = Payment::create([
                'sale_id' => $validated['sale_id'],
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'transaction_id' => $validated['transaction_id'] ?? null
            ]);

            // Update sale status
            $totalPaid = $sale->payments()->sum('amount');
            $status = ($totalPaid >= $sale->total_price) ? 'paid' : 'partially_paid';
            
            $sale->update([
                'status' => $status
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'sale_status' => $status
                ]
            ], 201);
        });
    }
}