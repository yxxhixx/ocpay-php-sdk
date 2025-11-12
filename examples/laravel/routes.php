<?php

/**
 * Laravel Routes Example
 * 
 * Add these routes to your routes/web.php file
 */

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Payment routes (require authentication)
Route::middleware('auth')->group(function () {
    // Initiate payment
    Route::get('/orders/{order}/checkout', [OrderController::class, 'checkout'])
        ->name('orders.checkout');
    
    // Payment callback (when customer returns from payment page)
    Route::get('/orders/{order}/callback', [OrderController::class, 'callback'])
        ->name('orders.callback');
    
    // View order
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');
});

