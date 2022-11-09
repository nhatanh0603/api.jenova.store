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

        $this->syncProducts($cart);

        return new CartResource(auth()->user()->cart->load('products'));
    }

    /* Thêm sản phẩm vào giỏ hàng */
    public function store(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = auth()->user()->cart;

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

            if($new_product_quantity_in_cart > 10)
                return response()->json([
                    'message' => 'You already have '. $product_quantity_in_cart .' quantity in cart. Unable to add selected quantity to cart as it would exceed your purchase limit (10).'
                ], 409);

            if($new_product_quantity_in_cart > $product->stock)
                return response()->json([
                    'message' => 'There are not enough products(Cart: '.
                    $new_product_quantity_in_cart .') in stock(Stock: '. $product->stock .').'
                ], 409);

            $cart->products()->updateExistingPivot($product->id, [
                'quantity' => $new_product_quantity_in_cart
            ]);
        }else {
            $cart->products()->attach($product, ['quantity' => $validated['quantity']]);
        }

        return new CartResource($cart->load('products'));
    }

    /* Tăng hoặc giảm số lượng sản phẩm */
    public function edit(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = auth()->user()->cart;

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

        $this->syncProducts($cart);

        if($validated['quantity'] > $product->stock)
            return response()->json([
                'message' => 'There are not enough products(Cart: '.
                $validated['quantity'] .') in stock(Stock: '. $product->stock .').'
            ], 409);

        $result = $cart->products()->updateExistingPivot($product->id, [
            'quantity' => $validated['quantity']
        ]);

        if(!$result)
            return false;

        return new CartResource($cart->load('products'));
    }

    /* Xóa sản phẩm khỏi giỏ hàng */
    public function destroy(CartRequest $request)
    {
        $validated = $request->validated();

        $cart = auth()->user()->cart;

        if(!$cart)
            return response()->json([
                'message' => 'Cart does not exist!'
            ], 404);

        $cart->products()->detach($validated['product_id']);

        $this->syncProducts($cart);

        return new CartResource($cart->load('products'));
    }

    protected function syncProducts(Cart $cart)
    {
        foreach ($cart->products as $product) {
            if($product->stock < $product->pivot->quantity)
                $cart->products()->updateExistingPivot($product->id, [
                    'quantity' => $product->stock
                ]);
        }
    }
}
