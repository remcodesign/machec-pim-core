<?php

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', RoleMiddleware::using('pim_admin')])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
