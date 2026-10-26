<?php

use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\ShippingTemplateController;
use App\Http\Controllers\Admin\GroupBuyCampaignController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{productId}', [CartController::class, 'remove'])->whereNumber('productId')->name('cart.remove');
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [OrderController::class, 'place'])->name('checkout.place');
    Route::get('/checkout/success', [OrderController::class, 'success'])->name('checkout.success');
});
Route::get('/locale/{locale}', [StorefrontController::class, 'setLocale'])->name('locale.set');
Route::get('/currency/{currency}', [StorefrontController::class, 'setCurrency'])->name('currency.set');
Route::post('/payments/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('payments.stripe.webhook');

Route::prefix('account')->name('account.')->group(function () {
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.submit');
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit');
    Route::get('/verify-email', [CustomerAuthController::class, 'showVerify'])->name('verify');
    Route::post('/verify-email', [CustomerAuthController::class, 'verify'])->name('verify.submit');
    Route::post('/verify-email/resend', [CustomerAuthController::class, 'resend'])->name('verify.resend');
    Route::middleware(['auth', EnsureCustomer::class])->group(function () {
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/return', [CustomerOrderController::class, 'requestReturn'])->name('orders.return');
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/ai/product-copy', [AiController::class, 'productCopy'])->name('ai.product-copy');
        Route::get('/ai', [SystemController::class, 'ai'])->name('ai.index');
        Route::get('/localization', [SystemController::class, 'localization'])->name('localization');
        Route::get('/settings', [SystemController::class, 'settings'])->name('settings');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
        Route::resource('returns', AdminReturnController::class)->only(['index', 'update']);
        Route::resource('customers', CustomerController::class)->only(['index', 'show']);
        Route::resource('group-buy', GroupBuyCampaignController::class)->parameters(['group-buy' => 'groupBuyCampaign'])->except(['show']);
        Route::resource('shipping', ShippingTemplateController::class)->parameters(['shipping' => 'shipping'])->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'destroy']);
    });
});
