<?php

namespace App\Http\Controllers;

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
        $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();

        if($order)
            return new OrderResource($order->with_order_details());
        else
            return response()->json([
                'message' => 'Order Not Found.'
            ], 404);
    }

    public function store()
    {
        $validated = request()->validate([
            'order' => 'required|array|filled'
        ]);

        $price_updated = false;
        $total_price = 0;

        foreach ($validated['order'] as $key => $item) {
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

            $order_details[$product->id]['name'] = $product->name;
            $order_details[$product->id]['slug'] = $product->slug;
            $order_details[$product->id]['display_name'] = $product->display_name;
            $order_details[$product->id]['primary_attribute'] = $product->extra_columns()[1]->value;
            $order_details[$product->id]['amount'] = $product->price;
            $order_details[$product->id]['quantity'] = $item['quantity'];

            $fixed_price_product[$key] = $product;
            $fixed_price_product[$key]->quantity = $item['quantity'];
            unset($fixed_price_product[$key]->categories);
        }

        if($price_updated)
            return response()->json([
                'message' => $message,
                'products' => new ProductSimpleCollection($fixed_price_product)
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
                'message' => 'Order Successfully Placed.',
                'order' => new OrderResource($order->with_order_details())
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'The server encountered an unexpected condition that prevented it from fulfilling the request.'
            ], 500);
        }
    }
}
