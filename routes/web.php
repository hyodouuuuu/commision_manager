<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')->name('dashboard');
Route::view('/orders', 'orders.index', ['activeNav' => 'orders'])->name('dashboard.orders');
Route::view('/customers', 'placeholder', [
    'title' => 'Customer',
    'description' => 'Simpan informasi customer dan riwayat commission.',
    'activeNav' => 'customers',
])->name('dashboard.customers');
Route::view('/packages', 'placeholder', [
    'title' => 'Paket Commission',
    'description' => 'Atur jenis paket commission dan harga default.',
    'activeNav' => 'packages',
])->name('dashboard.packages');
Route::view('/finance', 'placeholder', [
    'title' => 'Keuangan',
    'description' => 'Pantau pemasukan dan transaksi pembayaran commission.',
    'activeNav' => 'finance',
])->name('dashboard.finance');
