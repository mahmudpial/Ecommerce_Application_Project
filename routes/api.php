<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ModeratorDashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Resources\Api\UserResource;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new UserResource($request->user());
})->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {

    // -------------------------------------------------------
    // Authentication — customer-facing flows
    // -------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register/request-otp', [AuthController::class, 'registerRequestOtp']);
        Route::post('register/verify-otp', [AuthController::class, 'registerVerifyOtp']);

        Route::post('login/request-otp', [AuthController::class, 'loginRequestOtp']);
        Route::post('login/verify-otp', [AuthController::class, 'loginVerifyOtp']);

        Route::post('forgot-password/request-otp', [AuthController::class, 'forgotPasswordRequestOtp']);
        Route::post('forgot-password/verify-otp', [AuthController::class, 'forgotPasswordVerifyOtp']);
        Route::post('forgot-password/reset', [AuthController::class, 'resetPassword']);
    });

    // Legacy customer login aliases (kept for backward compat)
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

    // -------------------------------------------------------
    // Admin OTP authentication — PUBLIC (no token exists yet)
    // Must be outside the auth:sanctum middleware group because
    // the admin has no Bearer token before they log in.
    // -------------------------------------------------------
    Route::post('admin/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('admin/verify-otp', [AuthController::class, 'verifyOtp']);

    // -------------------------------------------------------
    // Authenticated user profile
    // -------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('checkout', [PaymentController::class, 'checkout']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
    });

    // -------------------------------------------------------
    // Public product & catalog browsing
    // -------------------------------------------------------
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/latest', [ProductController::class, 'latest']);
    Route::get('products/popular', [ProductController::class, 'popular']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}/products', [BrandController::class, 'products']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);

    // -------------------------------------------------------
    // Wishlist & Reviews
    // -------------------------------------------------------
    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist/add', [WishlistController::class, 'add']);
    Route::delete('wishlist/remove/{productId}', [WishlistController::class, 'remove']);
    Route::get('wishlist/check/{productId}', [WishlistController::class, 'check']);

    Route::get('reviews', [ReviewController::class, 'index']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::get('my-reviews', [ReviewController::class, 'myReviews']);
    Route::delete('remove', [ReviewController::class, 'remove']);

    // -------------------------------------------------------
    // Payment gateway
    // -------------------------------------------------------
    Route::post('sslcommerz/success', [PaymentController::class, 'paymentSuccess'])->name('sslc.success');
    Route::post('sslcommerz/failure', [PaymentController::class, 'paymentFailure'])->name('sslc.failure');
    Route::post('sslcommerz/cancel', [PaymentController::class, 'paymentCancel'])->name('sslc.cancel');
    Route::post('sslcommerz/ipn', [PaymentController::class, 'paymentIpn'])->name('sslc.ipn');

    // -------------------------------------------------------
    // Admin — protected (token + admin role required)
    // -------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // Admin dashboard overview
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        // Admin brand management
        Route::get('brands', [BrandController::class, 'adminIndex']);
        Route::get('brands/{brand}', [BrandController::class, 'show']);
        Route::post('brands', [BrandController::class, 'store']);
        Route::put('brands/{brand}', [BrandController::class, 'update']);
        Route::delete('brands/{brand}', [BrandController::class, 'destroy']);

        // Admin category management
        Route::get('categories', [CategoryController::class, 'adminIndex']);
        Route::get('categories/{category}', [CategoryController::class, 'show']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        // Admin product management
        Route::get('products', [ProductController::class, 'adminIndex']);
        Route::get('products/{product}', [ProductController::class, 'adminShow']);
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);

        // Admin user management
        Route::get('users', [UserController::class, 'index']);
        // Admin order management
        Route::get('orders', [OrderController::class, 'adminIndex']);
        Route::get('orders/{order}', [OrderController::class, 'adminShow']);
        Route::patch('orders/{order}', [OrderController::class, 'update']);

        // In admin middleware group:
        Route::get('orders/{order}', [OrderController::class, 'show']);               // order details
        Route::get('orders/{order}/invoice', [OrderController::class, 'generateInvoice']); // PDF

        // Reports
        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/products', [ReportController::class, 'products']);
        Route::get('reports/customers', [ReportController::class, 'customers']);
        Route::get('reports/orders', [ReportController::class, 'orders']);
    });

    // -------------------------------------------------------
    // Moderator
    // -------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:moderator,admin'])->prefix('moderator')->group(function () {
        Route::get('dashboard', [ModeratorDashboardController::class, 'index']);
        Route::get('reviews/pending', [ReviewController::class, 'pendingReviews']);
        Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject']);
    });

});
