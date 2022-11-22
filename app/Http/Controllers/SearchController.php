<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;

class SearchController extends Controller
{
    public function search($keyword)
    {
        return response()->json([
            'data' => ProductSimpleResource::collection(Product::search($keyword)->get())
        ]);
    }
}
