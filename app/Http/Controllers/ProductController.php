<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductSimpleCollection;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index($record = 15)
    {
        return new ProductSimpleCollection(Product::cursorPaginate($record));
    }

    public function random($quantum = 9)
    {
        $products = collect(ProductSimpleResource::collection(Product::inRandomOrder()->get()));

        return response()->json([
            'data' => [
                'strength' => $products->where('primary_attr', 0)->take($quantum)->values(),
                'agility' => $products->where('primary_attr', 1)->take($quantum)->values(),
                'intelligence' => $products->where('primary_attr', 2)->take($quantum)->values()
            ]
        ]);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if(!$product)
            return response(['error' => [
                    'statusMessage' => 'Product not found',
                    'statusCode' => 404
                ]
            ], 404);

        $product_attributes = $product->attributes()->toArray();
        $ability_position = 0;

        /* Duyệt tất cả attribute của product */
        foreach ($product_attributes as $key => $pa) {
            /* Rút từng phần tử của attribute array */
            $wil_be_removed = Arr::pull($product_attributes, $key);

            /* Nếu phần tử nào có child */
            if(isset($wil_be_removed['children'])) {
                /* Nếu product là hero */
                if($product->categories->contains(1)) {
                    /* Nếu phần tử là ability */
                    if(Str::of($wil_be_removed['name'])->test('/ability_/')) {
                        $product_attributes['abilities'] = [];
                    }

                    $ability_array[$ability_position]['name_slug'] = $pa['pivot']['value'];

                    foreach ($wil_be_removed['children'] as $key => $child) {
                        $ability_array[$ability_position][Str::remove($pa['name'] . '_', $child['name'])] = $child['pivot']['value'];
                    }

                    $product_attributes['abilities'] = $ability_array;
                    $ability_position++;
                }else {
                    $product_attributes[$pa['name']][$pa['name']] = $pa['pivot']['value'];

                    foreach ($wil_be_removed['children'] as $key => $child) {
                        $product_attributes[$pa['name']]['children'][$child['name']] = $child['pivot']['value'];
                    }
                }
            }else {
                $product_attributes[$pa['name']] = $pa['pivot']['value'];
            }
        }

        /* LỌC ABILITY CÓ SHARD VÀ SCEPTER */
        if(isset($product_attributes['abilities'])) {
            $scepter = $shard = [];

            foreach ($product_attributes['abilities'] as $key => $ability) {
                if(!$shard) {
                    if($ability['is_granted_by_shard'] == '1') {
                        Arr::pull($product_attributes['abilities'], $key);
                        $shard = $ability;
                    }

                    if($ability['has_shard'] == '1')
                        $shard = $ability;
                }

                if(!$scepter) {
                    if($ability['is_granted_by_scepter'] == '1') {
                        Arr::pull($product_attributes['abilities'], $key);
                        $scepter = $ability;
                    }

                    if($ability['has_scepter'] == '1')
                        $scepter = $ability;
                }
            }

            /* Xóa key vì đã pull ra một vài key (0, 1, 3, 4...) */
            $product_attributes['abilities'] = collect($product_attributes['abilities'])->values()->toArray();
            $product_attributes['abilities']['aghanim_shard'] = $shard;
            $product_attributes['abilities']['aghanim_scepter'] = $scepter;
        }

        $product = collect($product)->merge($product_attributes);

        return new ProductDetailResource($product);
    }
}
