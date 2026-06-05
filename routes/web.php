<?php

declare(strict_types=1);

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/items/create', [CatalogController::class, 'create'])->name('catalog.items.create');
Route::post('/catalog/items', [CatalogController::class, 'store'])->name('catalog.items.store');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.items.add');
Route::delete('/cart/items', [CartController::class, 'remove'])->name('cart.items.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/card', [CheckoutController::class, 'card'])->name('checkout.card');
Route::post('/checkout/bank-deposit', [CheckoutController::class, 'bankDeposit'])->name('checkout.bank-deposit');
Route::post('/checkout/cash-on-delivery', [CheckoutController::class, 'cashOnDelivery'])->name('checkout.cash-on-delivery');
Route::post('/checkout/cash-on-hand', [CheckoutController::class, 'cashOnHand'])->name('checkout.cash-on-hand');

Route::get('/orders/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
