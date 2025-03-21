<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /**
     * Process a checkout request and create an order
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_type' => 'required|string|in:card,paypal,cashOnDelivery',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'card_details' => 'nullable|required_if:payment_type,card|array',
            'card_details.card_name' => 'nullable|required_if:payment_type,card|string',
            'card_details.card_number' => 'nullable|required_if:payment_type,card|string',
            'card_details.expiration_date' => 'nullable|required_if:payment_type,card|string',
            'card_details.cvv' => 'nullable|required_if:payment_type,card|string',
            'save_card' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Validate address belongs to user
        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If payment method provided, validate it belongs to user
        $paymentMethod = null;
        if ($request->has('payment_method_id')) {
            $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$paymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method_id' => ['Invalid payment method']
                ]);
            }

            // Ensure payment method type matches payment type
            if ($paymentMethod->type !== $request->payment_type) {
                throw ValidationException::withMessages([
                    'payment_method_id' => ['Payment method type does not match payment type']
                ]);
            }
        }

        // Get user's cart
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty']
            ]);
        }

        // Start a database transaction
        return DB::transaction(function () use ($user, $cart, $address, $paymentMethod, $request) {
            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_type' => $request->payment_type,
                'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
                'address_id' => $address->id,
                'subtotal' => $cart->items->sum('total_price'),
                'discount_amount' => $cart->discount_amount,
                'coupon_code' => $cart->coupon_code,
                'tax_amount' => $this->calculateTax($cart),
                'shipping_amount' => $this->calculateShipping($cart),
                'total' => $this->calculateTotal($cart),
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'name' => $cartItem->product->name,
                    'price' => $cartItem->unit_price,
                    'quantity' => $cartItem->quantity,
                    'unit' => $cartItem->product->unit,
                    'pricing_type' => $cartItem->product->pricing_type,
                    'total_price' => $cartItem->total_price,
                ]);
            }

            // Process payment based on payment type
            switch ($request->payment_type) {
                case 'card':
                    // Process card payment
                    $paymentResult = $this->processCardPayment($order, $request);
                    break;

                case 'paypal':
                    // Process PayPal payment
                    $paymentResult = $this->processPayPalPayment($order, $request);
                    break;

                case 'cashOnDelivery':
                    // No payment processing needed for COD
                    $paymentResult = [
                        'success' => true,
                        'status' => 'pending',
                        'message' => 'Order placed successfully. Payment will be collected on delivery.'
                    ];
                    break;

                default:
                    throw ValidationException::withMessages([
                        'payment_type' => ['Unsupported payment type']
                    ]);
            }

            // Update order payment status based on payment result
            $order->payment_status = $paymentResult['status'];
            $order->save();

            // Save card if requested
            if ($request->payment_type === 'card' &&
                !$paymentMethod &&
                $request->has('save_card') &&
                $request->save_card) {
                $this->saveCardDetails($user, $request->card_details);
            }

            // Clear the cart after successful order
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->update([
                'discount_amount' => null,
                'coupon_code' => null,
            ]);

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order,
                'payment_result' => $paymentResult,
            ]);
        });
    }

    /**
     * Process card payment
     */
    private function processCardPayment($order, $request)
    {
        // In a real application, you would integrate with a payment processor
        // For this example, we'll simulate a successful payment

        // If using a saved payment method
        if ($request->has('payment_method_id')) {
            // Process payment with saved card
            return [
                'success' => true,
                'status' => 'paid',
                'message' => 'Payment processed successfully using saved card'
            ];
        }

        // Process payment with new card details
        // Validate card details in a real implementation
        if ($request->has('card_details')) {
            // Process new card payment
            return [
                'success' => true,
                'status' => 'paid',
                'message' => 'Payment processed successfully'
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'Invalid card details'
        ];
    }

    /**
     * Process PayPal payment
     */
    private function processPayPalPayment($order, $request)
    {
        // In a real application, you would integrate with PayPal
        // For this example, we'll simulate a successful payment
        return [
            'success' => true,
            'status' => 'paid',
            'message' => 'PayPal payment processed successfully'
        ];
    }

    /**
     * Save card details as a payment method
     */
    private function saveCardDetails($user, $cardDetails)
    {
        // Mask the card number
        $cardNumber = $cardDetails['card_number'];
        $lastFour = substr($cardNumber, -4);
        $maskedNumber = str_repeat('*', strlen($cardNumber) - 4) . $lastFour;

        // Extract card type (simplified - in a real app use a proper card type detection)
        $cardType = 'Unknown';
        if (preg_match('/^4/', $cardNumber)) {
            $cardType = 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            $cardType = 'MasterCard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            $cardType = 'American Express';
        } elseif (preg_match('/^6(?:011|5)/', $cardNumber)) {
            $cardType = 'Discover';
        }

        // Create a name for the card
        $cardName = $cardType . ' ending in ' . $lastFour;

        // Save as a new payment method
        PaymentMethod::create([
            'user_id' => $user->id,
            'type' => 'card',
            'name' => $cardName,
            'details' => [
                'card_number' => $maskedNumber,
                'expiry' => $cardDetails['expiration_date'],
                'last_four' => $lastFour,
                'card_type' => $cardType,
                'card_name' => $cardDetails['card_name']
            ],
            'is_default' => true,
        ]);

        // Set all other payment methods to non-default
        PaymentMethod::where('user_id', $user->id)
            ->where('type', 'card')
            ->where('name', '!=', $cardName)
            ->update(['is_default' => false]);
    }

    /**
     * Calculate tax for the order
     */
    private function calculateTax($cart)
    {
        // Simplified tax calculation - in a real application, you would have
        // more complex logic based on shipping address, product type, etc.
        $taxRate = 0.1; // 10% tax rate
        $taxableAmount = $cart->items->sum('total_price') - ($cart->discount_amount ?? 0);
        return round($taxableAmount * $taxRate, 2);
    }

    /**
     * Calculate shipping cost
     */
    private function calculateShipping($cart)
    {
        // Simplified shipping calculation - in a real application, you would have
        // more complex logic based on shipping address, product weight, etc.
        $subtotal = $cart->items->sum('total_price');

        // Free shipping for orders over $50
        if ($subtotal >= 50) {
            return 0;
        }

        // Base shipping cost
        return 5.99;
    }

    /**
     * Calculate the total order amount
     */
    private function calculateTotal($cart)
    {
        $subtotal = $cart->items->sum('total_price');
        $discount = $cart->discount_amount ?? 0;
        $tax = $this->calculateTax($cart);
        $shipping = $this->calculateShipping($cart);

        return round($subtotal + $shipping + $tax - $discount, 2);
    }
}
