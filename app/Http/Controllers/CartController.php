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
            return response()->json([
                'message' => 'Cart does not exist.'
            ], 404);

        /* return new CartResource(auth()->user()->cart->load(['products' => function ($query) {
            $query->orderBy('cart_products.updated_at', 'desc');
        }])); */
        return new CartResource(auth()->user()->cart->load('products'));
    }

    /* Thêm sản phẩm vào giỏ hàng */
    public function store(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = Cart::find(auth()->user()->cart->id);

        if(!$cart)
            $cart = Cart::create([
                'user_id' => auth()->user()->id
            ]);

        $product = Product::find($validated['product_id']);

        if(!$product)
            return response()->json([
                'message' => 'Product does not exist.'
            ], 404);

        if($product->stock == 0)
            return response()->json([
                'message' => 'Product is out of stock.'
            ], 409);

        if($cart->products->contains($product)) {
            $product_quantity_in_cart = $cart->products->where('id', $product->id)->first()->pivot->quantity;

            $new_product_quantity_in_cart = $product_quantity_in_cart + $validated['quantity'];

            if($new_product_quantity_in_cart > $product->stock)
                return response()->json([
                    'message' => 'There are not enough products in stock.'
                ], 409);

            return $cart->products()->updateExistingPivot($product->id, [
                'quantity' => $new_product_quantity_in_cart
            ]);
        }

        return $cart->products()->attach($product, ['quantity' => $validated['quantity']]);
    }

    /* Tăng hoặc giảm số lượng sản phẩm */
    public function edit(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = Cart::find(auth()->user()->cart->id);

        if(!$cart)
            return response()->json([
                'message' => 'Cart does not exist.'
            ], 404);

        $product = $cart->products->where('id', $validated['product_id'])->first();

        if(!$product)
            return response()->json([
                'message' => 'Product does not exist.'
            ], 404);

        if($product->stock == 0)
            return response()->json([
                'message' => 'Product is out of stock.'
            ], 409);

        if($validated['quantity'] > $product->stock)
            return response()->json([
                'message' => 'There are not enough products in stock.'
            ], 409);

        return $cart->products()->updateExistingPivot($product->id, [
            'quantity' => $validated['quantity']
        ]);
    }

    /* Xóa sản phẩm khỏi giỏ hàng */
    public function destroy(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = Cart::find(auth()->user()->cart->id);

        if(!$cart)
            return response()->json([
                'message' => 'Cart does not exist!'
            ], 404);

        $result = $cart->products()->detach($validated['product_id']);

        if(!$result)
            return false;
        return new CartResource($cart->load('products'));
    }
}
