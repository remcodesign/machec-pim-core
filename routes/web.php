<?php

use App\Livewire\PimCatalog\UserCreate;
use App\Livewire\PimCatalog\UserIndex;
use App\Livewire\PimCatalog\UserShow;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', RoleMiddleware::using('pim_admin')])->group(function (): void {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('users', UserIndex::class)->name('users.index');
    Route::livewire('users/create', UserCreate::class)->name('users.create');
    Route::livewire('users/{user}', UserShow::class)->name('users.show')->whereNumber('user');
});

require __DIR__.'/settings.php';
