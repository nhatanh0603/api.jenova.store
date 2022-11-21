<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_id'];
    protected $hidden = ['deleted_at'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'cart_products')
                    ->where('stock', '>', 0)
                    ->withPivot('quantity')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sync_product_quantity()
    {
        foreach ($this->products as $product) {
            if($product->stock < $product->pivot->quantity)
                $this->products()->updateExistingPivot($product->id, [
                    'quantity' => $product->stock
                ]);
        }
    }
}
