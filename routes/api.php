<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderAddonController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('customers', CustomerController::class);
Route::apiResource('packages', PackageController::class);

Route::get('orders/monthly', [OrderController::class, 'monthly'])->name('orders.monthly');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::apiResource('orders', OrderController::class);

Route::get('orders/{order}/addons', [OrderAddonController::class, 'index'])->name('orders.addons.index');
Route::post('orders/{order}/addons', [OrderAddonController::class, 'store'])->name('orders.addons.store');
Route::match(['put', 'patch'], 'orders/{order}/addons/{addon}', [OrderAddonController::class, 'update'])->name('orders.addons.update');
Route::delete('orders/{order}/addons/{addon}', [OrderAddonController::class, 'destroy'])->name('orders.addons.destroy');

Route::get('orders/{order}/payments', [PaymentController::class, 'index'])->name('orders.payments.index');
Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
Route::match(['put', 'patch'], 'orders/{order}/payments/{payment}', [PaymentController::class, 'update'])->name('orders.payments.update');
Route::delete('orders/{order}/payments/{payment}', [PaymentController::class, 'destroy'])->name('orders.payments.destroy');
