<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search($keyword)
    {
        return response()->json([
            'data' => ProductSimpleResource::collection(Product::search($keyword)->get())
        ]);
    }

    public function searchOrder($keyword)
    {
        $orders = DB::table('orders')
                    ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                    ->select('orders.*')->distinct()
                    ->where('orders.user_id', '=', auth()->user()->id)
                    ->where(function($query) use ($keyword) {
                        $query->where('orders.id', 'like', '%' . $keyword . '%')
                        ->orWhere('order_details.display_name', 'like', '%' .
                            ucwords(strtolower($keyword)) . '%');
                    })->orderByDesc('orders.created_at')->get();

        return OrderResource::collection($orders);
    }
}
