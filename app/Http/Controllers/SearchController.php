<?php

namespace App\Http\Controllers;

use App\Models\Product;

class SearchController extends Controller
{
    public function search($keyword)
    {
        return response()->json([
            'data' => Product::search($keyword)->get()
        ]);
    }
}
