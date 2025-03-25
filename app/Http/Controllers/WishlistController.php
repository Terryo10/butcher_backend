<?php

namespace App\Http\Controllers;

use App\Models\WishlistItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display user's wishlist.
     */
    public function index()
    {
        $wishlistItems = WishlistItem::with('product')
            ->where('user_id', Auth::id())
            ->get()
            ->map(function ($item) {
                return $item->product;
            });

        return response()->json([
            'success' => true,
            'data' => $wishlistItems
        ]);
    }

    /**
     * Add a product to wishlist.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();

        // Check if already in wishlist
        $exists = WishlistItem::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'Product is already in your wishlist',
            ]);
        }

        // Add to wishlist
        WishlistItem::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist successfully',
        ]);
    }

    /**
     * Remove a product from wishlist.
     */
    public function destroy($productId)
    {
        $userId = Auth::id();

        $deleted = WishlistItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found in wishlist',
        ], 404);
    }

    /**
     * Check if a product is in user's wishlist
     */
    public function check($productId)
    {
        $userId = Auth::id();

        $exists = WishlistItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();

        return response()->json([
            'success' => true,
            'in_wishlist' => $exists
        ]);
    }
}

