<?php

// DeliveryNotification Model
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'delivery_id',
        'order_id',
        'title',
        'body',
        'data',
        'read',
        'read_at',
        'sent',
        'channel',
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
        'sent' => 'boolean',
        'data' => 'array',
    ];

    /**
     * Get the user associated with the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the delivery associated with the notification.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the order associated with the notification.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): self
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the notification as sent.
     */
    public function markAsSent(): self
    {
        $this->update([
            'sent' => true,
        ]);

        return $this;
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope a query to only include unsent notifications.
     */
    public function scopeUnsent($query)
    {
        return $query->where('sent', false);
    }
}
