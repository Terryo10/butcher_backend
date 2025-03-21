<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Calculate and update the total price based on quantity and unit price
    public function updateTotalPrice()
    {
        $product = $this->product;
        $this->unit_price = $product->price;

        if ($product->isWeightBased()) {
            $this->total_price = $product->price * $this->quantity;
        } else {
            $this->total_price = $product->price * floor($this->quantity);
        }

        return $this;
    }
}
