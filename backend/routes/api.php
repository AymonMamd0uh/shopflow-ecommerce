<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;


// Authentication Routes
Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});


// Public Routes
Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);

Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);


// Customer Routes
Route::middleware('auth:sanctum')->group(function () {

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::patch('/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Checkout & Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

});


// Admin Routes
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        // Products Management
        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show']);

        // Categories Management
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show']);

        // Orders Management
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{order}', [OrderController::class, 'adminShow']);

        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

        Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus']);

    });