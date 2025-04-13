<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseService;

class DeviceController extends Controller
{
    /**
     * Register a user device for notifications
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android,web',
        ]);

        $user = Auth::user();

        // Update or create device token
        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_token' => $validated['device_token'],
            ],
            [
                'device_type' => $validated['device_type'],
                'active' => true,
                'last_used_at' => now(),
            ]
        );

        // Subscribe to appropriate topics based on user role
        $firebaseService = app(FirebaseService::class);

        if ($user->hasRole('driver')) {
            $firebaseService->subscribeToTopic($validated['device_token'], 'driver_notifications');
        }

        if ($user->hasRole('admin')) {
            $firebaseService->subscribeToTopic($validated['device_token'], 'admin_notifications');
        }

        return response()->json(['message' => 'Device registered successfully', 'device_id' => $device->id]);
    }

    /**
     * Unregister a device
     */
    public function unregister(Request $request)
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
        ]);

        $user = Auth::user();

        // Deactivate the device
        UserDevice::where('user_id', $user->id)
            ->where('device_token', $validated['device_token'])
            ->update(['active' => false]);

        // Unsubscribe from topics
        $firebaseService = app(FirebaseService::class);

        if ($user->hasRole('driver')) {
            $firebaseService->unsubscribeFromTopic($validated['device_token'], 'driver_notifications');
        }

        if ($user->hasRole('admin')) {
            $firebaseService->unsubscribeFromTopic($validated['device_token'], 'admin_notifications');
        }

        return response()->json(['message' => 'Device unregistered successfully']);
    }
}
