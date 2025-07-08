<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Return paginated list of products
        $products = Product::select([
            'id',
            'name',
            'barcode',
            'price',
            'cost_price'
        ])->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}