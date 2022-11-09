<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $hidden = ['status'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'attribute_products')
                    ->using(AttributeProduct::class)
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
