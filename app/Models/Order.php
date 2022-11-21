<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'unique_product', 'total_price'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_details')
                    ->withPivot('name', 'slug', 'display_name', 'primary_attribute', 'amount', 'quantity')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function update_product_stock()
    {
        foreach ($this->products as $order_product) {
            $product = Product::find($order_product->id);
            $product->stock -= $order_product->pivot->quantity;
            $product->save();
        }
    }
}
