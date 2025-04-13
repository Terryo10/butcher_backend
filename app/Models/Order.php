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
        'payment_reference',
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

    /**
     * Get the transaction associated with the order
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function orderTotal()
    {
        $subtotal = $this->subtotal ?? 0;
        $shipping = $this->shipping_amount ?? 0;
        $tax = $this->tax_amount ?? 0;
        $discount = $this->discount_amount ?? 0;

        return round($subtotal + $shipping + $tax - $discount, 2);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Create a delivery for this order.
     */
    public function createDelivery(array $attributes = []): Delivery
    {
        $pendingStatusId = DeliveryStatus::where('name', 'pending')->first()->id;

        $deliveryData = array_merge([
            'delivery_status_id' => $pendingStatusId,
            'delivery_fee' => $this->shipping_amount,
        ], $attributes);

        $delivery = $this->delivery()->create($deliveryData);

        // Create notification for admins
        DeliveryNotification::create([
            'type' => 'new_order',
            'delivery_id' => $delivery->id,
            'order_id' => $this->id,
            'title' => 'New Order For Delivery',
            'body' => "New order #{$this->order_number} requires delivery",
            'data' => [
                'delivery_id' => $delivery->id,
                'order_number' => $this->order_number,
                'customer_name' => $this->user->name,
                'total' => $this->total,
            ],
        ]);

        return $delivery;
    }

    /**
     * Observer method to create delivery automatically on order creation.
     */
    protected static function booted()
    {
        parent::booted();

        static::created(function ($order) {
            if ($order->requires_delivery && $order->payment_type === 'cash_on_delivery') {
                $order->createDelivery();
            }
        });
    }
}
