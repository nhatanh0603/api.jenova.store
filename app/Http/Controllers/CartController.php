<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\ProductSimpleCollection;
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

        $cart->sync_product_quantity();

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

    /* Checkout sản phẩm được chọn trong giỏ hàng */
    public function checkout()
    {
        $validated = request()->validate([
            'checkout' => 'required|array'
        ]);

        foreach ($validated['checkout'] as $item) {
            $product = Product::find($item['id']);

            if(!$product)
                return response()->json([
                    'message' => 'Product does not exist.'
                ], 404);
            else
                if($product->stock < $item['quantity'])
                    return response()->json([
                        'message' => 'Some products are not enough units in stock. Your cart will be updated. Please try again.'
                        //Some product information in your order has been updated, please go back to cart page and try again.
                    ], 409);
                else {
                    //$id_array[] = $item['id'];
                    $product->quantity = $item['quantity'];
                    $checkout[] = $product;
                }
        }

        /* Dòng này để sắp xếp lại thứ tự theo thời gian add to cart */
        //$checkout = auth()->user()->cart->products->whereIn('id', $id_array);

        return new ProductSimpleCollection($checkout);
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

        $cart->sync_product_quantity();

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

        $cart->sync_product_quantity();

        return new CartResource($cart->load('products'));
    }
}
