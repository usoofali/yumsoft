<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\Customer;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function getStock(Shop $shop, Request $request)
    {
        // Verify user has access to this shop
        if (!$request->user()->canAccessShop($shop->id)) {
            abort(403, 'Unauthorized access');
        }

        $stock = ShopStock::where('shop_id', $shop->id)
            ->with(['product' => function($query) {
                $query->select(['id', 'name', 'barcode', 'price']);
            }])
            ->get(['product_id', 'quantity']);

        return response()->json([
            'success' => true,
            'data' => $stock->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => $item->product
                ];
            })
        ]);
    }

    public function getCustomers(Shop $shop, Request $request)
    {
        // Verify user has access to this shop
        if (!$request->user()->canAccessShop($shop->id)) {
            abort(403, 'Unauthorized access');
        }

        $customers = Customer::where('shop_id', $shop->id)
            ->select([
                'id',
                'name',
                'phone',
                'email',
                'address',
                'credit_limit'
            ])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
}