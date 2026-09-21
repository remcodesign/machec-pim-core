<?php

use App\Http\Controllers\Api\CatalogCategoriesController;
use App\Http\Controllers\Api\CatalogFacetsController;
use App\Http\Controllers\Api\CatalogProductsController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Middleware\RecordServiceTokenUsageMiddleware;
use Illuminate\Support\Facades\Route;

/**
 * Sanctum-token-gated catalog read API (D40/D41), called directly by the
 * BFF — never by a logged-in admin session. The token itself is minted by
 * `pim-core`'s own `ServiceClient` at Step 3.8 (D99).
 */
Route::middleware(['auth:sanctum', RecordServiceTokenUsageMiddleware::class])->prefix('v1')->group(function (): void {
    Route::middleware('abilities:catalog:read')->group(function (): void {
        Route::get('/products', CatalogProductsController::class);
        Route::get('/categories', CatalogCategoriesController::class);
        Route::get('/facets', CatalogFacetsController::class);
    });

    Route::middleware('abilities:stock:movements:write')->group(function (): void {
        Route::post('/stock/movements', StockMovementController::class);
    });
});
