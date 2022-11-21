<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

class Product extends Model
{
    use Searchable;
    use HasFactory;

    protected $hidden = ['status'];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    #[SearchUsingPrefix(['id', 'name', 'slug'])]
    #[SearchUsingFullText(['display_name'])]
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
        ];
    }

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

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_details');
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
