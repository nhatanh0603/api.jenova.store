<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $hidden = ['status'];

    public function attributes()
    {
        $attributes = $this->belongsToMany(Attribute::class, 'attribute_products')
                    ->using(AttributeProduct::class)
                    ->withPivot('value')
                    ->withTimestamps();

        return gatherChilds($attributes->get());
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_products')->withTimestamps();
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_products')->withTimestamps();
    }

    public function extra_columns($column = [3, 10, 11, 12]) //complexity, attack_type
    {
        $is_hero = $this->categories->contains(1);

        if($is_hero) {
            return DB::table('attribute_products', 'ap')
                    ->join('attributes', 'ap.attribute_id', '=', 'attributes.id')
                    ->select('attributes.name as name', 'ap.value as value')->where('product_id', $this->id)
                    ->whereIn('attribute_id', $column)->orderBy('product_id')->get();
        }
        return false;
    }
}
