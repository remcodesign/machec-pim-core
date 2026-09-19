<?php

use App\Http\Controllers\Api\AdminProductController;
use Illuminate\Support\Facades\Route;

/**
 * REVISIT AT STEP 3.3: this route is session-guarded (`auth`, the same web
 * guard the Livewire admin uses), not Sanctum — Step 3.2's own spec text
 * never mentions Sanctum for this endpoint, unlike Step 3.3's explicit
 * `catalog:read` Sanctum-token ability (D41). That means a genuine external
 * "machine caller" (the endpoint's own stated audience) cannot actually
 * authenticate against this route today — only a logged-in pim_admin
 * browser session can. Once Step 3.3 installs Sanctum for the read API,
 * decide then whether this write endpoint also needs a scoped ability
 * (e.g. `products:write`, D33/D41/D99's pattern) instead of staying
 * session-only. See tests/Feature/PimCatalog/AdminProductControllerTest.php
 * for the test that will need to switch from `actingAs()` to
 * `Sanctum::actingAs()` (or add a second guard) if this route's middleware
 * changes.
 */
Route::middleware(['auth', 'role:pim_admin'])->group(function (): void {
    Route::post('/admin/v1/products', AdminProductController::class);
});
