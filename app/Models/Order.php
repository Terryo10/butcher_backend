<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'payment_type',
        'payment_method_id',
        'address_id',
        'subtotal',
        'shipping_amount',
        'tax_amount',
        'discount_amount',
        'coupon_code',
        'total',
        'notes',
        'tracking_number',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'shipping_amount' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Generate a unique order number
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = rand(100, 999);
        return $prefix . $timestamp . $random;
    }
}
