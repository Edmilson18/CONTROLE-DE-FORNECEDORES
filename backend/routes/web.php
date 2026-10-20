<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('password', [AuthController::class, 'password'])->middleware('throttle:5,1');
    });
});

Route::get('/', function () {
    return view('welcome');
});
