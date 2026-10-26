<?php

use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/locale/{locale}', [StorefrontController::class, 'setLocale'])->name('locale.set');
Route::get('/currency/{currency}', [StorefrontController::class, 'setCurrency'])->name('currency.set');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/ai/product-copy', [AiController::class, 'productCopy'])->name('ai.product-copy');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'destroy']);
    });
});
