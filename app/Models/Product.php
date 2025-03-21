<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'pricing_type',
        'unit',
        'weight',
        'min_quantity',
        'max_quantity',
        'increment',
        'stock',
        'image',
        'images',
        'description',
        'subcategory_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'max_quantity' => 'decimal:2',
        'increment' => 'decimal:2',
        'images' => 'array',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Helper method to check if this is a weight-based product
    public function isWeightBased()
    {
        return $this->pricing_type === 'weight';
    }

    // Calculate price for a given quantity
    public function calculatePrice($quantity)
    {
        if ($this->isWeightBased()) {
            return $this->price * $quantity;
        }
        return $this->price * floor($quantity); // For fixed price items, we use whole numbers
    }
}