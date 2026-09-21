<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\ContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Api\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\Admin\WaitlistController as AdminWaitlistController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WaitlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public)
|--------------------------------------------------------------------------
*/

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

Route::get('articles', [ArticleController::class, 'index'])->name('storefront.articles.index');
Route::get('articles/{article}', [ArticleController::class, 'show']);

Route::get('reviews', [ReviewController::class, 'index']);
Route::post('reviews', [ReviewController::class, 'store'])->middleware('throttle:5,1');

Route::get('delivery-zones', [DeliveryZoneController::class, 'index']);

Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
Route::get('orders/{order}', [OrderController::class, 'show']);
// Called when the customer returns from Paystack. Rate-limited because it is
// public and reaches out to Paystack on each call.
Route::post('orders/{order}/verify-payment', [OrderController::class, 'verifyPayment'])
    ->middleware('throttle:20,1');

// Paystack's own callback. No auth and no throttle: it authenticates itself
// with a signature, and throttling it would lose payments.
Route::post('paystack/webhook', PaystackWebhookController::class);

Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1');
Route::post('waitlist', [WaitlistController::class, 'store'])->middleware('throttle:5,1');
Route::post('newsletter', [NewsletterController::class, 'store'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Admin dashboard
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:admin', 'admin.active'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('password', [AuthController::class, 'updatePassword']);

        Route::get('dashboard', DashboardController::class);

        Route::apiResource('products', AdminProductController::class);
        Route::patch('products/{product}/toggle', [AdminProductController::class, 'toggle']);

        Route::apiResource('articles', AdminArticleController::class);
        Route::patch('articles/{article}/toggle', [AdminArticleController::class, 'toggle']);

        // Signature for browser-to-Cloudinary uploads. Throttled because each
        // call mints credentials good for one upload.
        Route::post('uploads/signature', [UploadController::class, 'signature'])
            ->middleware('throttle:30,1');

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::delete('orders/{order}', [AdminOrderController::class, 'destroy']);

        Route::get('messages', [ContactMessageController::class, 'index']);
        Route::get('messages/{message}', [ContactMessageController::class, 'show']);
        Route::patch('messages/{message}/handled', [ContactMessageController::class, 'toggleHandled']);
        Route::delete('messages/{message}', [ContactMessageController::class, 'destroy']);

        Route::get('reviews', [AdminReviewController::class, 'index']);
        Route::patch('reviews/{review}/toggle', [AdminReviewController::class, 'toggle']);
        Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

        // Exports are declared before the {signup} routes so "export" is never
        // mistaken for an id.
        Route::get('waitlist/export', [AdminWaitlistController::class, 'export']);
        Route::get('waitlist', [AdminWaitlistController::class, 'index']);
        Route::patch('waitlist/{signup}/invited', [AdminWaitlistController::class, 'toggleInvited']);
        Route::delete('waitlist/{signup}', [AdminWaitlistController::class, 'destroy']);

        Route::get('subscribers/export', [AdminNewsletterController::class, 'export']);
        Route::get('subscribers', [AdminNewsletterController::class, 'index']);
        Route::delete('subscribers/{subscriber}', [AdminNewsletterController::class, 'destroy']);

        Route::get('delivery-zones', [AdminDeliveryZoneController::class, 'index']);
        Route::post('delivery-zones', [AdminDeliveryZoneController::class, 'store']);
        Route::put('delivery-zones/{deliveryZone}', [AdminDeliveryZoneController::class, 'update']);
        Route::delete('delivery-zones/{deliveryZone}', [AdminDeliveryZoneController::class, 'destroy']);

        // Team management is owner-only.
        Route::middleware('admin.role:owner')->group(function () {
            Route::get('team', [AdminController::class, 'index']);
            Route::post('team', [AdminController::class, 'store']);
            Route::put('team/{admin}', [AdminController::class, 'update']);
            Route::delete('team/{admin}', [AdminController::class, 'destroy']);
        });
    });
});
