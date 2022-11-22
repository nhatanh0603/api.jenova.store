<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory;

    protected $hidden = ['status'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_products')->withTimestamps();
    }

    public function products_with_bonus($column, $direction)
    {
        $raw = [
            'one_liner' => 'MAX(IF(attributes.`name` = \'one_liner\', attribute_products.`value`, NULL)) AS one_liner',
            'primary_attr' => 'MAX(IF(attributes.`name` = \'primary_attr\', attribute_products.`value`, NULL)) AS primary_attr',
            'complexity' => 'MAX(IF(attributes.`name` = \'complexity\', attribute_products.`value`, NULL)) AS complexity',
            'attack_capability' => 'MAX(IF(attributes.`name` = \'attack_capability\', attribute_products.`value`, NULL)) AS attack_capability'
        ];

        return DB::table('attribute_products')
                        ->join('products', 'products.id', '=', 'attribute_products.product_id')
                        ->join('attributes', 'attributes.id', '=', 'attribute_products.attribute_id')
                        ->join('category_products', 'products.id', '=', 'category_products.product_id')
                        ->select('products.*', DB::raw($raw['one_liner']), DB::raw($raw['primary_attr']), DB::raw($raw['complexity']), DB::raw($raw['attack_capability']))
                        ->whereIn('attributes.id', [3, 10, 11, 12])
                        ->where('category_products.category_id', $this->id)
                        ->groupBy('products.id')
                        ->orderBy($column, $direction);
    }
}
