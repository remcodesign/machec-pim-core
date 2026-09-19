<?php

use App\Http\Controllers\Api\CatalogCategoriesController;
use App\Http\Controllers\Api\CatalogProductsController;
use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;

/**
 * Sanctum-token-gated catalog read API (D40/D41), called directly by the
 * BFF — never by a logged-in admin session. The token itself is minted by
 * `pim-core`'s own `ServiceClient` at Step 3.8 (D99); that model doesn't
 * exist yet, so nothing here can actually issue a `catalog:read` token
 * until then — tests authenticate via `Sanctum::actingAs()` instead.
 */
Route::middleware(['auth:sanctum', 'ability:catalog:read'])->prefix('v1')->group(function (): void {
    Route::get('/products', CatalogProductsController::class);
    Route::get('/categories', CatalogCategoriesController::class);
});

Route::middleware(['auth:sanctum', 'ability:stock:movements:write'])->prefix('v1')->group(function (): void {
    Route::post('/stock/movements', StockMovementController::class);
});
