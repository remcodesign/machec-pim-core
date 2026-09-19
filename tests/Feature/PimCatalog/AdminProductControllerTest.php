<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

function validProductPayload(Category $category): array
{
    return [
        'sku' => 'test-sku-1',
        'name' => 'Test Product',
        'brand' => 'Test Brand',
        'price_cents' => 895,
        'category_slug' => $category->slug,
        'status' => 'published',
        'attributes' => ['amperage' => '16A'],
    ];
}

/**
 * REVISIT AT STEP 3.3: both tests below authenticate with the plain
 * session-based `actingAs($user)` because routes/api.php currently guards
 * this route with `auth` (web session), not Sanctum — see the note on that
 * route. `actingAs()` sets the resolved user directly, bypassing the real
 * HTTP auth mechanism entirely, so these tests pass regardless of which
 * guard the middleware actually uses; they do NOT prove a real external
 * "machine caller" (no browser session) can call this endpoint. If Step
 * 3.3 changes this route's middleware to `auth:sanctum` (+ an ability),
 * switch these to `Sanctum::actingAs($admin, ['products:write'])` (or
 * whichever ability is chosen) so the test actually exercises the guard
 * that's really in front of the route.
 */
test('pim_admin creates a product, leaving embedding null (D109)', function (): void {
    Role::findOrCreate('pim_admin');
    $admin = User::factory()->create();
    $admin->assignRole('pim_admin');

    $category = Category::create([
        'slug' => 'test-category',
        'name' => 'Test Category',
        'filterable_attributes' => ['amperage'],
    ]);

    Cache::store('redis')->forget('cache_gen:pim_products');

    $response = $this->actingAs($admin)->postJson('/api/admin/v1/products', validProductPayload($category));

    $response->assertCreated()
        ->assertJson(['sku' => 'test-sku-1']);

    $product = Product::where('sku', 'test-sku-1')->firstOrFail();

    expect($product->name)->toBe('Test Product')
        ->and($product->brand)->toBe('Test Brand')
        ->and($product->price->cents)->toBe(895)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->status)->toBe(ProductStatus::Published)
        ->and($product->getAttribute('attributes'))->toBe(['amperage' => '16A'])
        ->and($product->embedding)->toBeNull()
        ->and((int) Cache::store('redis')->get('cache_gen:pim_products'))->toBe(1);
});

test('rejects a product creation from a non-pim_admin role with 403', function (): void {
    $user = User::factory()->create();

    $category = Category::create([
        'slug' => 'test-category',
        'name' => 'Test Category',
        'filterable_attributes' => ['amperage'],
    ]);

    $response = $this->actingAs($user)->postJson('/api/admin/v1/products', validProductPayload($category));

    $response->assertForbidden();

    expect(Product::count())->toBe(0);
});
