<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{

    public function index(): JsonResponse
    {
        $products = Product::latest()->get();
        
        return response()->json([
            'data' => ProductResource::collection($products),
            'count' => $products->count(),
        ]);
    }
}
