<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductSimpleCollection;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index($record = 5)
    {
        return OrderResource::collection(auth()->user()->orders()->cursorPaginate($record));
    }

    public function show($id)
    {
        $order = DB::table('orders')->where('user_id', auth()->user()->id)->where('id', $id)->first();
        $order->order_detail = DB::table('order_details')->where('order_id', $id)->get();
        return new OrderResource($order);
    }

    public function store()
    {
        $validated = request()->validate([
            'order' => 'required|array|filled'
        ]);

        $price_updated = false;
        $total_price = 0;

        foreach ($validated['order'] as $item) {
            $product = Product::find($item['id']);

            /* Kiểm tra product có tồn tại không? */
            if(!$product)
                return response()->json([
                    'message' => 'Some products do not exist, please go back to cart page and try again.'
                ], 410);
            else {
                /* Kiểm tra số lượng có đủ không? */
                if($product->stock < $item['quantity'])
                    return response()->json([
                        'message' => 'Some products are not enough units in stock, please go back to cart page and try again.'
                    ], 409);

                /* Kiểm tra đơn giá có thay đổi không? */
                if($product->price != $item['price']) {
                    $price_updated = true;
                    $message = 'The prices of some products in your order has been updated.';
                }
            }

            $id_array[] = $item['id'];
            $total_price += $product->price * $item['quantity'];

            //$product->extra_columns();
            $order_details[$product->id]['name'] = $product->name;
            $order_details[$product->id]['slug'] = $product->slug;
            $order_details[$product->id]['display_name'] = $product->display_name;
            $order_details[$product->id]['primary_attribute'] = $product->extra_columns()[1]->value;
            $order_details[$product->id]['amount'] = $product->price;
            $order_details[$product->id]['quantity'] = $item['quantity'];
        }

        if($price_updated)
            return response()->json([
                'message' => $message,
                'products' => new ProductSimpleCollection(auth()->user()->cart->products->whereIn('id', $id_array))
            ], 409);

        try {
            $order = DB::transaction(function () use ($total_price, $order_details, $validated, $id_array) {
                $order = Order::create([
                    'user_id' => auth()->user()->id,
                    'unique_product' => count($validated['order']),
                    'total_price' => $total_price
                ]);

                $order->products()->attach($order_details);

                $order->update_product_stock();

                auth()->user()->cart->products()->detach($id_array);

                return $order;
            });

            return response()->json([
                'message' => [
                    'first' => 'Order Successfully Placed.',
                    'second' => 'Thank You For Your Purchase!',
                    'order' => $order
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The server encountered an unexpected condition that prevented it from fulfilling the request.'
            ], 500);
        }
    }
}
