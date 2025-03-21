<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    private function getOrCreateCart()
    {
        if (Auth::check()) {
            // User is logged in, get or create cart by user_id
            $cart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['session_id' => session()->getId()]
            );
        } else {
            // User is not logged in, get or create cart by session_id
            $sessionId = session()->getId();
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }

        return $cart;
    }

    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cartItems = [];

        foreach ($cart->items as $item) {
            $product = $item->product;
            $cartItems[] = [
                'id' => $item->id,
                'name' => $product->name,
                'price' => $product->price,
                'pricing_type' => $product->pricing_type,
                'unit' => $product->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'image' => $product->image,
                'product_id' => $product->id
            ];
        }

        return response()->json([
            'items' => $cartItems,
            'total' => $cart->items->sum('total_price')
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01'
        ]);

        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($request->product_id);

        // Validate quantity based on product type
        if ($product->isWeightBased()) {
            // For weight-based products
            if ($product->min_quantity && $request->quantity < $product->min_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Minimum order quantity is {$product->min_quantity} {$product->unit}"
                ]);
            }

            if ($product->max_quantity && $request->quantity > $product->max_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Maximum order quantity is {$product->max_quantity} {$product->unit}"
                ]);
            }

            if ($product->increment) {
                // Check if quantity is a valid increment
                $isValidIncrement = round(($request->quantity - ($product->min_quantity ?? 0)) / $product->increment, 10) % 1 === 0;
                if (!$isValidIncrement) {
                    throw ValidationException::withMessages([
                        'quantity' => "Quantity must be in increments of {$product->increment} {$product->unit}"
                    ]);
                }
            }
        } else {
            // For fixed price products, ensure quantity is a whole number
            if (floor($request->quantity) != $request->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Quantity must be a whole number for this product"
                ]);
            }
        }

        // Check if product is already in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update existing cart item
            $cartItem->quantity += $request->quantity;
            $cartItem->updateTotalPrice();
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = new CartItem([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
            $cartItem->updateTotalPrice();
            $cartItem->save();
        }

        return $this->index();
    }

    public function updateCartItem(Request $request, $id)
    {
        $cart = $this->getOrCreateCart();
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $id)
            ->firstOrFail();

        $product = $cartItem->product;

        $request->validate([
            'quantity' => 'required|numeric|min:0'
        ]);

        // If quantity is 0, remove the item
        if ($request->quantity == 0) {
            $cartItem->delete();
            return $this->index();
        }

        // Validate quantity based on product type
        if ($product->isWeightBased()) {
            // For weight-based products
            $request->validate([
                'quantity' => 'numeric|min:0.01'
            ]);

            if ($product->min_quantity && $request->quantity < $product->min_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Minimum order quantity is {$product->min_quantity} {$product->unit}"
                ]);
            }

            if ($product->max_quantity && $request->quantity > $product->max_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Maximum order quantity is {$product->max_quantity} {$product->unit}"
                ]);
            }

            if ($product->increment) {
                // Check if quantity is a valid increment
                $isValidIncrement = round(($request->quantity - ($product->min_quantity ?? 0)) / $product->increment, 10) % 1 === 0;
                if (!$isValidIncrement) {
                    throw ValidationException::withMessages([
                        'quantity' => "Quantity must be in increments of {$product->increment} {$product->unit}"
                    ]);
                }
            }
        } else {
            // For fixed price products, ensure quantity is a whole number
            $request->validate([
                'quantity' => 'integer|min:1'
            ]);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->updateTotalPrice();
        $cartItem->save();

        return $this->index();
    }

    public function removeFromCart($id)
    {
        $cart = $this->getOrCreateCart();
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $id)
            ->firstOrFail();

        $cartItem->delete();

        return $this->index();
    }

    public function clearCart()
    {
        $cart = $this->getOrCreateCart();
        CartItem::where('cart_id', $cart->id)->delete();

        return response()->json(['message' => 'Cart cleared successfully']);
    }

    // Method to merge guest cart with user cart after login
    public function mergeCart(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User is not authenticated'], 401);
        }

        $sessionId = $request->session_id;
        $guestCart = Cart::where('session_id', $sessionId)->first();

        if ($guestCart) {
            $userCart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['session_id' => session()->getId()]
            );

            // Merge guest cart items into user cart
            foreach ($guestCart->items as $guestItem) {
                $userItem = CartItem::where('cart_id', $userCart->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($userItem) {
                    // Update existing cart item
                    $userItem->quantity += $guestItem->quantity;
                    $userItem->updateTotalPrice();
                    $userItem->save();
                } else {
                    // Create new cart item in user cart
                    $newItem = new CartItem([
                        'cart_id' => $userCart->id,
                        'product_id' => $guestItem->product_id,
                        'quantity' => $guestItem->quantity
                    ]);
                    $newItem->updateTotalPrice();
                    $newItem->save();
                }
            }

            // Delete guest cart
            $guestCart->delete();
        }

        return $this->index();
    }
}
