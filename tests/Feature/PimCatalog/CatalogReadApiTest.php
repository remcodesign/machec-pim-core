<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function createCategory(array $overrides = []): Category
{
    return Category::create(array_merge([
        'slug' => 'test-category',
        'name' => 'Test Category',
        'filterable_attributes' => ['amperage'],
    ], $overrides));
}

function createProduct(Category $category, array $overrides = []): Product
{
    return Product::create(array_merge([
        'sku' => 'sku-'.uniqid(),
        'category_id' => $category->id,
        'name' => 'Test Product',
        'brand' => 'Test Brand',
        'price_cents' => 1000,
        'status' => 'published',
        'attributes' => ['amperage' => '16A'],
    ], $overrides));
}

test('returns only products modified since the given timestamp, paginated', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();

    $old = createProduct($category, ['sku' => 'old-sku']);
    DB::table('pim_products')->where('id', $old->id)->update(['updated_at' => now()->subDays(2)]);

    createProduct($category, ['sku' => 'recent-sku']);

    $response = $this->getJson('/api/v1/products?modified_since='.urlencode(now()->subDay()->toIso8601String()));

    $response->assertOk();
    $skus = collect($response->json('data'))->pluck('sku');

    expect($skus)->toContain('recent-sku')
        ->and($skus)->not->toContain('old-sku');
});

test('rejects invalid product catalog query values', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);

    $response = $this->getJson('/api/v1/products?modified_since=not-a-date&price_min=-1');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['modified_since', 'price_min']);
});

test('rejects an invalid category modified_since value', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);

    $response = $this->getJson('/api/v1/categories?modified_since=not-a-date');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['modified_since']);
});

test('rejects a product price range where the minimum exceeds the maximum', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);

    $response = $this->getJson('/api/v1/products?price_min=200&price_max=100');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['price_min', 'price_max']);
});

test('returns the full paginated collection when modified_since is omitted', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'sku-a']);
    createProduct($category, ['sku' => 'sku-b']);

    $response = $this->getJson('/api/v1/products');

    $response->assertOk();
    $skus = collect($response->json('data'))->pluck('sku');

    expect($skus)->toContain('sku-a')
        ->and($skus)->toContain('sku-b');
});

test('filters products by category, brand, and a category-specific attribute together', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $matching = createCategory(['slug' => 'matching-category', 'filterable_attributes' => ['amperage']]);
    $other = createCategory(['slug' => 'other-category']);

    createProduct($matching, ['sku' => 'match', 'brand' => 'EMAT', 'attributes' => ['amperage' => '16A']]);
    createProduct($matching, ['sku' => 'wrong-brand', 'brand' => 'ABB', 'attributes' => ['amperage' => '16A']]);
    createProduct($matching, ['sku' => 'wrong-attribute', 'brand' => 'EMAT', 'attributes' => ['amperage' => '40A']]);
    createProduct($other, ['sku' => 'wrong-category', 'brand' => 'EMAT', 'attributes' => ['amperage' => '16A']]);

    $response = $this->getJson('/api/v1/products?category=matching-category&brand=EMAT&amperage=16A');

    $response->assertOk();
    $skus = collect($response->json('data'))->pluck('sku');

    expect($skus->all())->toBe(['match']);
});

test('returns filterable_attributes on each category in the categories response', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    createCategory(['slug' => 'with-filters', 'filterable_attributes' => ['amperage', 'component_type']]);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonFragment(['slug' => 'with-filters', 'filterable_attributes' => ['amperage', 'component_type']]);
});

test('rejects a call whose token lacks the catalog:read ability', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['some:other-ability']);
    createCategory();

    $response = $this->getJson('/api/v1/products');

    $response->assertForbidden();
});

test('an attribute key not in that category\'s filterable_attributes is silently ignored, not a 422', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['filterable_attributes' => ['amperage']]);
    createProduct($category, ['sku' => 'still-returned']);

    $response = $this->getJson('/api/v1/products?category='.$category->slug.'&not_a_real_attribute=whatever');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('sku'))->toContain('still-returned');
});

test('a filtered products call still returns only one page of results, page size 6', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();

    foreach (range(1, 8) as $i) {
        createProduct($category, ['sku' => "page-test-{$i}"]);
    }

    $response = $this->getJson('/api/v1/products?category='.$category->slug);

    $response->assertOk();

    expect($response->json('per_page'))->toBe(6)
        ->and($response->json('total'))->toBe(8)
        ->and(collect($response->json('data')))->toHaveCount(6);
});

test('sort=price_desc orders the response by price_cents descending', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'cheap', 'price_cents' => 100]);
    createProduct($category, ['sku' => 'expensive', 'price_cents' => 900]);
    createProduct($category, ['sku' => 'middle', 'price_cents' => 500]);

    $response = $this->getJson('/api/v1/products?category='.$category->slug.'&sort=price_desc');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['expensive', 'middle', 'cheap']);
});

test('an unrecognized sort value falls back to name_asc instead of erroring', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'zebra', 'name' => 'Zebra Product']);
    createProduct($category, ['sku' => 'apple', 'name' => 'Apple Product']);

    $response = $this->getJson('/api/v1/products?category='.$category->slug.'&sort=not_a_real_sort');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['apple', 'zebra']);
});
