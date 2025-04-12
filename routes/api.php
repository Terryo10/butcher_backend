<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public user info route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public product and category routes
Route::get('categories', [CategoryController::class, 'index']);
Route::get('subcategories', [SubCategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::post('/search/{name}', [ProductController::class, 'search']);

// Authentication routes
Route::prefix('auth')->group(function () {
    // Public auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);


    // Password reset routes
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Social Authentication Routes
    Route::get('google', [AuthController::class, 'redirectToGoogle']);
    Route::post('google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::get('apple', [AuthController::class, 'redirectToApple']);
    Route::get('apple/callback', [AuthController::class, 'handleAppleCallback']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected routes requiring authentication
Route::middleware('auth:sanctum')->group(function () {
    // Cart routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::put('/update/{id}', [CartController::class, 'updateCartItem']);
        Route::delete('/remove/{id}', [CartController::class, 'removeFromCart']);
        Route::delete('/clear', [CartController::class, 'clearCart']);
        Route::post('/coupon/apply', [CartController::class, 'applyCoupon']);
        Route::delete('/coupon/remove', [CartController::class, 'removeCoupon']);
        Route::post('/sync', [CartController::class, 'mergeCart']);
    });

    // Address routes
    Route::prefix('user/addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::post('/{id}/default', [AddressController::class, 'setDefault']);
    });

    // Payment method routes
    Route::prefix('user/payment-methods')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::post('/', [PaymentMethodController::class, 'store']);
        Route::put('/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
        Route::post('/{id}/default', [PaymentMethodController::class, 'setDefault']);
    });

    // Checkout route
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::post('/ecocash/initiate/{orderId}', [CheckoutController::class, 'processEcocashPayment']);
    Route::post('/ecocash/check/{transactionId}', [CheckoutController::class, 'checkEcocashPayment']);

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        Route::get('/{id}/tracking', [OrderController::class, 'tracking']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist', [WishlistController::class, 'store']);
    Route::delete('wishlist/{productId}', [WishlistController::class, 'destroy']);
    Route::get('wishlist/check/{productId}', [WishlistController::class, 'check']);
});
