<?php

use App\Livewire\PimCatalog\AdminDashboard;
use App\Livewire\PimCatalog\CategoryForm;
use App\Livewire\PimCatalog\CategoryIndex;
use App\Livewire\PimCatalog\ProductForm;
use App\Livewire\PimCatalog\ProductIndex;
use App\Livewire\PimCatalog\ProductStock;
use App\Livewire\PimCatalog\ServiceClientCreate;
use App\Livewire\PimCatalog\ServiceClientIndex;
use App\Livewire\PimCatalog\UserCreate;
use App\Livewire\PimCatalog\UserIndex;
use App\Livewire\PimCatalog\UserShow;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', RoleMiddleware::using('pim_admin')])->group(function (): void {
    Route::livewire('dashboard', AdminDashboard::class)->name('dashboard');

    Route::livewire('users', UserIndex::class)->name('users.index');
    Route::livewire('users/create', UserCreate::class)->name('users.create');
    Route::livewire('users/{user}', UserShow::class)->name('users.show')->whereNumber('user');

    Route::livewire('service-clients', ServiceClientIndex::class)->name('service-clients.index');
    Route::livewire('service-clients/create', ServiceClientCreate::class)->name('service-clients.create');

    Route::livewire('products', ProductIndex::class)->name('products.index');
    Route::livewire('products/create', ProductForm::class)->name('products.create');
    Route::livewire('products/{product}/edit', ProductForm::class)->name('products.edit')->whereNumber('product');
    Route::livewire('products/{product}/stock', ProductStock::class)->name('products.stock')->whereNumber('product');

    Route::livewire('categories', CategoryIndex::class)->name('categories.index');
    Route::livewire('categories/create', CategoryForm::class)->name('categories.create');
    Route::livewire('categories/{category}/edit', CategoryForm::class)->name('categories.edit')->whereNumber('category');
});

require __DIR__.'/settings.php';
