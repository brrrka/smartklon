<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerStateController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Auth Routes
// -------------------------------------------------------
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// -------------------------------------------------------
// Protected Routes (requires authentication)
// -------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Stock Management
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('/stock/items', [StockController::class, 'storeItem'])->name('stock.items.store');
    Route::get('/stock/items/{item}/tags', [StockController::class, 'getTagsByItem'])->name('stock.items.tags');


    // Scanner State
    Route::post('/scanner/mode', [ScannerStateController::class, 'update'])->name('scanner.mode.update');
    Route::get('/scanner/mode', [ScannerStateController::class, 'current'])->name('scanner.mode.current');
});
