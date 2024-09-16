<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

Route::middleware('guest:sanctum')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout', [AuthController::class, 'logout']);

    Route::prefix('/products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
    
        Route::post('/', [ProductController::class, 'store']);
    
        Route::put('/{product:slug}', [ProductController::class, 'update']);
    
        Route::delete('/{product}', [ProductController::class, 'delete']);
    });
});

Route::get('products/{product:slug}', [ProductController::class, 'show']);
