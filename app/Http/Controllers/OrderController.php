<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Get all orders for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::where('user_id', $user->id)
            ->with(['items'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Paginate results
        $perPage = $request->get('per_page', 10);
        $orders = $query->paginate($perPage);

        return response()->json([
            'orders' => $orders
        ]);
    }

    /**
     * Get a specific order
     */
    public function show($id)
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['items', 'address', 'paymentMethod'])
            ->firstOrFail();

        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancel($id)
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only allow cancellation of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled'
            ], 422);
        }

        // Update order status
        $order->status = 'cancelled';
        $order->save();

        // If payment was already made, initiate refund process
        if ($order->payment_status === 'paid') {
            // In a real application, you would integrate with payment provider to process refund
            $order->payment_status = 'refunded';
            $order->save();
        }

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }

    /**
     * Get order tracking information
     */
    public function tracking($id)
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // In a real application, you would integrate with a shipping provider
        // to get real-time tracking information
        $trackingInfo = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'estimated_delivery' => null,
            'tracking_history' => [],
        ];

        // Mock some tracking data based on order status
        switch ($order->status) {
            case 'pending':
                $trackingInfo['tracking_history'][] = [
                    'date' => $order->created_at->format('Y-m-d H:i:s'),
                    'status' => 'Order received',
                    'location' => 'Online',
                ];
                break;

            case 'processing':
                $trackingInfo['tracking_history'] = [
                    [
                        'date' => $order->created_at->format('Y-m-d H:i:s'),
                        'status' => 'Order received',
                        'location' => 'Online',
                    ],
                    [
                        'date' => $order->updated_at->format('Y-m-d H:i:s'),
                        'status' => 'Order processing',
                        'location' => 'Warehouse',
                    ],
                ];
                break;

            case 'shipped':
                $trackingInfo['estimated_delivery'] = now()->addDays(3)->format('Y-m-d');
                $trackingInfo['tracking_history'] = [
                    [
                        'date' => $order->created_at->format('Y-m-d H:i:s'),
                        'status' => 'Order received',
                        'location' => 'Online',
                    ],
                    [
                        'date' => $order->created_at->addHours(2)->format('Y-m-d H:i:s'),
                        'status' => 'Order processing',
                        'location' => 'Warehouse',
                    ],
                    [
                        'date' => $order->updated_at->format('Y-m-d H:i:s'),
                        'status' => 'Order shipped',
                        'location' => 'Distribution Center',
                    ],
                ];
                break;

            case 'delivered':
                $trackingInfo['tracking_history'] = [
                    [
                        'date' => $order->created_at->format('Y-m-d H:i:s'),
                        'status' => 'Order received',
                        'location' => 'Online',
                    ],
                    [
                        'date' => $order->created_at->addHours(2)->format('Y-m-d H:i:s'),
                        'status' => 'Order processing',
                        'location' => 'Warehouse',
                    ],
                    [
                        'date' => $order->created_at->addHours(8)->format('Y-m-d H:i:s'),
                        'status' => 'Order shipped',
                        'location' => 'Distribution Center',
                    ],
                    [
                        'date' => $order->updated_at->format('Y-m-d H:i:s'),
                        'status' => 'Order delivered',
                        'location' => 'Customer Address',
                    ],
                ];
                break;

            case 'cancelled':
                $trackingInfo['tracking_history'] = [
                    [
                        'date' => $order->created_at->format('Y-m-d H:i:s'),
                        'status' => 'Order received',
                        'location' => 'Online',
                    ],
                    [
                        'date' => $order->updated_at->format('Y-m-d H:i:s'),
                        'status' => 'Order cancelled',
                        'location' => 'Online',
                    ],
                ];
                break;
        }

        return response()->json([
            'tracking' => $trackingInfo
        ]);
    }
}
