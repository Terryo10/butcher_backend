<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'driver_id',
        'delivery_status_id',
        'assigned_at',
        'picked_up_at',
        'in_transit_at',
        'delivered_at',
        'unassign_reason',
        'delivery_fee',
        'delivery_notes',
        'recipient_signature',
        'location_updates',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_notes' => 'array',
        'location_updates' => 'array',
    ];

    /**
     * Get the order associated with the delivery.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the driver (user) associated with the delivery.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * User relationship for delivery notes and activities.
     * This is a utility relationship to resolve user names.
     */
    public function user()
    {
        // This is a utility relationship that doesn't actually reflect 
        // a database relationship but allows Filament to work with user_id fields in arrays
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * Get the status of the delivery.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DeliveryStatus::class, 'delivery_status_id');
    }

    /**
     * Get the activities for this delivery.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DeliveryActivity::class);
    }

    /**
     * Get the notifications for this delivery.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(DeliveryNotification::class);
    }

    /**
     * Assign a driver to this delivery.
     */
    public function assignDriver(User $driver): self
    {
        $previousStatus = $this->status->name;
        $pendingStatusId = DeliveryStatus::where('name', 'pending')->first()->id;
        $assignedStatusId = DeliveryStatus::where('name', 'assigned')->first()->id;

        if ($this->delivery_status_id === $pendingStatusId) {
            $this->update([
                'driver_id' => $driver->id,
                'delivery_status_id' => $assignedStatusId,
                'assigned_at' => now(),
            ]);

            // Log activity
            $this->activities()->create([
                'user_id' => Auth::id() ?? $driver->id,
                'activity_type' => 'assigned',
                'previous_status' => $previousStatus,
                'new_status' => 'assigned',
                'notes' => 'Driver assigned to delivery',
            ]);

            // Create notification
            DeliveryNotification::create([
                'user_id' => $this->order->user_id, // Customer notification
                'type' => 'driver_assigned',
                'delivery_id' => $this->id,
                'order_id' => $this->order_id,
                'title' => 'Driver Assigned',
                'body' => "Driver {$driver->name} has been assigned to your order #{$this->order->order_number}",
                'data' => [
                    'delivery_id' => $this->id,
                    'driver_name' => $driver->name,
                ],
            ]);
        }

        return $this;
    }

    /**
     * Unassign a driver from this delivery.
     */
    public function unassignDriver(string $reason = null): self
    {
        $previousStatus = $this->status->name;
        $unassignedStatusId = DeliveryStatus::where('name', 'unassigned')->first()->id;
        $pendingStatusId = DeliveryStatus::where('name', 'pending')->first()->id;

        $driverId = $this->driver_id;
        $driverName = $this->driver ? $this->driver->name : 'Unknown driver';

        $this->update([
            'driver_id' => null,
            'delivery_status_id' => $pendingStatusId,
            'unassign_reason' => $reason,
        ]);

        // Log activity
        $this->activities()->create([
            'user_id' => Auth::id() ?? $driverId,
            'activity_type' => 'unassigned',
            'previous_status' => $previousStatus,
            'new_status' => 'pending',
            'notes' => $reason ?? 'Driver unassigned from delivery',
        ]);

        // Create notification for admins
        DeliveryNotification::create([
            'type' => 'driver_unassigned',
            'delivery_id' => $this->id,
            'order_id' => $this->order_id,
            'title' => 'Delivery Unassigned',
            'body' => "Driver {$driverName} has unassigned from order #{$this->order->order_number}" .
                ($reason ? ". Reason: {$reason}" : ""),
            'data' => [
                'delivery_id' => $this->id,
                'reason' => $reason,
            ],
        ]);

        return $this;
    }

    /**
     * Update delivery status.
     */
    public function updateStatus(string $statusName, array $additionalData = []): self
    {
        $previousStatus = $this->status->name;
        $newStatusId = DeliveryStatus::where('name', $statusName)->first()->id;

        $updateData = ['delivery_status_id' => $newStatusId];

        // Update specific timestamp based on status
        if ($statusName === 'picked_up') {
            $updateData['picked_up_at'] = now();
        } elseif ($statusName === 'in_transit') {
            $updateData['in_transit_at'] = now();
        } elseif ($statusName === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        // Update location if provided
        if (isset($additionalData['location'])) {
            $locations = $this->location_updates ?? [];
            $locations[] = [
                'status' => $statusName,
                'timestamp' => now()->toIso8601String(),
                'coordinates' => $additionalData['location'],
            ];
            $updateData['location_updates'] = $locations;
        }

        // Add notes if provided
        if (isset($additionalData['notes'])) {
            $notes = $this->delivery_notes ?? [];
            $notes[] = [
                'status' => $statusName,
                'timestamp' => now()->toIso8601String(),
                'note' => $additionalData['notes'],
                'user_id' => Auth::id(),
            ];
            $updateData['delivery_notes'] = $notes;
        }

        // Add signature if provided
        if (isset($additionalData['signature'])) {
            $updateData['recipient_signature'] = $additionalData['signature'];
        }

        $this->update($updateData);

        // Log activity
        $this->activities()->create([
            'user_id' => Auth::id() ?? $this->driver_id,
            'activity_type' => 'status_changed',
            'previous_status' => $previousStatus,
            'new_status' => $statusName,
            'notes' => $additionalData['notes'] ?? "Status updated to {$statusName}",
            'metadata' => array_filter($additionalData, function($key) {
                return !in_array($key, ['notes']);
            }, ARRAY_FILTER_USE_KEY),
        ]);

        // Create notifications
        DeliveryNotification::create([
            'user_id' => $this->order->user_id, // Customer notification
            'type' => 'status_update',
            'delivery_id' => $this->id,
            'order_id' => $this->order_id,
            'title' => 'Delivery Status Update',
            'body' => "Your order #{$this->order->order_number} status is now: " . ucfirst($statusName),
            'data' => [
                'delivery_id' => $this->id,
                'status' => $statusName,
                'previous_status' => $previousStatus,
            ],
        ]);

        return $this;
    }

    /**
     * Add a note to the delivery.
     */
    public function addNote(string $note): self
    {
        $notes = $this->delivery_notes ?? [];
        $notes[] = [
            'timestamp' => now()->toIso8601String(),
            'note' => $note,
            'user_id' => Auth::id(),
        ];

        $this->update([
            'delivery_notes' => $notes,
        ]);

        // Log activity
        $this->activities()->create([
            'user_id' => Auth::id(),
            'activity_type' => 'note_added',
            'previous_status' => $this->status->name,
            'new_status' => $this->status->name,
            'notes' => $note,
        ]);

        return $this;
    }

    /**
     * Scope a query to only include pending deliveries.
     */
    public function scopePending($query)
    {
        $pendingStatusId = DeliveryStatus::where('name', 'pending')->first()->id;
        return $query->where('delivery_status_id', $pendingStatusId);
    }

    /**
     * Scope a query to only include assigned deliveries.
     */
    public function scopeAssigned($query)
    {
        $assignedStatusId = DeliveryStatus::where('name', 'assigned')->first()->id;
        return $query->where('delivery_status_id', $assignedStatusId);
    }

    /**
     * Scope a query to only include in-progress deliveries.
     */
    public function scopeInProgress($query)
    {
        $statuses = DeliveryStatus::whereIn('name', ['assigned', 'picked_up', 'in_transit'])->pluck('id');
        return $query->whereIn('delivery_status_id', $statuses);
    }

    /**
     * Scope a query to only include completed deliveries.
     */
    public function scopeCompleted($query)
    {
        $completedStatusId = DeliveryStatus::where('name', 'delivered')->first()->id;
        return $query->where('delivery_status_id', $completedStatusId);
    }

    /**
     * Scope a query to only include deliveries assigned to a specific driver.
     */
    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }
}
