<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;

class CartController extends Controller
{
    /* Lấy danh sách sản phẩm trong giỏ hàng */
    public function show()
    {
        $cart = auth()->user()->cart;

        if(!$cart)
            return response()->json(['error' => [
                'message' => 'Cart does not exist!',
                'statusCode' => 404
                ]
            ], 404);

        return new CartResource(auth()->user()->cart->load('products'));
    }

    /* Thêm sản phẩm vào giỏ hàng */
    public function store(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = Cart::find($validated['cart_id']);

        if(!$cart)
            $cart = Cart::create([
                'user_id' => auth()->user()->id
            ]);
            /* return response()->json(['error' => [
                'message' => 'Cart does not exist!',
                'statusCode' => 404
                ]
            ], 404); */

        $product = Product::find($validated['product_id']);

        if(!$product)
            return response()->json(['error' => [
                'message' => 'Product does not exist!',
                'statusCode' => 404
                ]
            ], 404);

        if($cart->products->contains($product)) {
            $product_quantity_in_cart = $cart->products->where('id', $product->id)->first()->pivot->quantity;

            $new_product_quantity_in_cart = $product_quantity_in_cart + $validated['quantity'];

            if($new_product_quantity_in_cart > $product->stock)
                return response()->json([
                    'message' => 'Product is out of stock!'
                ]);

            return $cart->products()->updateExistingPivot($product->id, [
                'quantity' => $new_product_quantity_in_cart
            ]);
        }

        return $cart->products()->attach($product, ['quantity' => $validated['quantity']]);
    }

    /* Xóa sản phẩm khỏi giỏ hàng */
    public function destroy(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = Cart::find($validated['cart_id']);

        if(!$cart)
            return response()->json(['error' => [
                'message' => 'Cart does not exist!',
                'statusCode' => 404
                ]
            ], 404);

        return $cart->products()->detach($validated['product_id']);
    }
}
