<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'total',
        'poll_url',
        'isPaid',
        'details'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'details' => 'array',
        'isPaid' => 'boolean',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
