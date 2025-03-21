<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        $paymentMethods = $user->paymentMethods;

        return response()->json([
            'payment_methods' => $paymentMethods
        ]);
    }

    /**
     * Store a new payment method
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:card,paypal',
            'name' => 'required|string|max:100',
            'details' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Handle card-specific validation if type is card
        if ($request->type === 'card') {
            $request->validate([
                'details.card_number' => 'required|string',
                'details.expiry' => 'required|string',
                'details.last_four' => 'required|string|size:4',
                'details.card_type' => 'required|string',
            ]);

            // Mask the card number for security
            if (isset($request->details['card_number'])) {
                $lastFour = substr($request->details['card_number'], -4);
                $request->details['last_four'] = $lastFour;
                $request->details['card_number'] = str_repeat('*', strlen($request->details['card_number']) - 4) . $lastFour;
            }
        }

        // If this is the first payment method or is_default is true, update all other methods
        $isDefault = $request->input('is_default', false);
        if ($isDefault || $user->paymentMethods->count() === 0) {
            // Set all existing payment methods to non-default
            PaymentMethod::where('user_id', $user->id)
                ->update(['is_default' => false]);
            $isDefault = true;
        }

        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'name' => $request->name,
            'details' => $request->details,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Payment method added successfully',
            'payment_method' => $paymentMethod
        ], 201);
    }

    /**
     * Update an existing payment method
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'details' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If setting as default, update all other payment methods
        if ($request->has('is_default') && $request->is_default) {
            PaymentMethod::where('user_id', $user->id)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);
        }

        $paymentMethod->update($request->only(['name', 'details', 'is_default']));

        return response()->json([
            'message' => 'Payment method updated successfully',
            'payment_method' => $paymentMethod
        ]);
    }

    /**
     * Delete a payment method
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If this was the default payment method, set another one as default
        if ($paymentMethod->is_default) {
            $newDefault = PaymentMethod::where('user_id', $user->id)
                ->where('id', '!=', $paymentMethod->id)
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $paymentMethod->delete();

        return response()->json([
            'message' => 'Payment method deleted successfully'
        ]);
    }

    /**
     * Set a payment method as default
     */
    public function setDefault($id)
    {
        $user = Auth::user();
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Set all payment methods to non-default
        PaymentMethod::where('user_id', $user->id)
            ->update(['is_default' => false]);

        // Set selected payment method as default
        $paymentMethod->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default payment method updated successfully',
            'payment_method' => $paymentMethod
        ]);
    }
}
