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

    public function products_with_bonus($params)
    {
        $columns_selected = [
            'products.id AS id',
            'products.name AS name',
            'products.slug AS slug',
            'products.display_name AS display_name',
            'products.price AS price',
            'products.stock AS stock',
            DB::raw('MAX(IF(attributes.`name` = \'one_liner\', attribute_products.`value`, NULL)) AS one_liner'),
            DB::raw('MAX(IF(attributes.`name` = \'primary_attr\', attribute_products.`value`, NULL)) AS primary_attr'),
            DB::raw('MAX(IF(attributes.`name` = \'complexity\', attribute_products.`value`, NULL)) AS complexity'),
            DB::raw('MAX(IF(attributes.`name` = \'attack_capability\', attribute_products.`value`, NULL)) AS attack_capability')
        ];

        $products = DB::table('attribute_products')
                    ->join('products', 'products.id', '=', 'attribute_products.product_id')
                    ->join('attributes', 'attributes.id', '=', 'attribute_products.attribute_id')
                    ->select($columns_selected)
                    ->whereIn('attributes.id', [3, 10, 11, 12])
                    ->groupBy('products.id');

        return DB::table('category_products')
                 ->joinSub($products, 'extra_products', function($join) use ($params) {
                    $join->on('extra_products.id', '=', 'category_products.product_id')
                         ->where('category_products.category_id', $this->id)
                         ->when(isset($params['primary_attr']), function($query) use ($params) {
                            $query->where('primary_attr', $params['primary_attr']);
                         })
                         ->when(isset($params['attack_capability']), function($query) use ($params) {
                            $query->where('attack_capability', $params['attack_capability']);
                         })
                         ->when(isset($params['complexity']), function($query) use ($params) {
                            $query->where('complexity', $params['complexity']);
                         });
                 })->select('extra_products.*')->orderBy($params['column'], $params['direction']);
    }
}
