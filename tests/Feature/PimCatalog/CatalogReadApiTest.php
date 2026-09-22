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

test('accepts price_min alone, with no price_max, instead of 422ing on a comparison rule with nothing to compare against', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'cheap', 'price_cents' => 100]);
    createProduct($category, ['sku' => 'expensive', 'price_cents' => 400000]);

    $response = $this->getJson('/api/v1/products?price_min=4000');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['expensive']);
});

test('accepts price_max alone, with no price_min, instead of 422ing on a comparison rule with nothing to compare against', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'cheap', 'price_cents' => 100]);
    createProduct($category, ['sku' => 'expensive', 'price_cents' => 400000]);

    $response = $this->getJson('/api/v1/products?price_max=4000');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['cheap']);
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

test('product listing queries always order by id after the requested sort column', function (): void {
    // `paginate()` runs page 1 and page 2 as two separate LIMIT/OFFSET
    // queries, at two different moments. `ORDER BY name ASC` alone doesn't
    // guarantee those two queries agree on the relative order of rows that
    // tie on `name` — Postgres can resolve that differently between them (a
    // different scan plan, stats refreshed in between) — which surfaces as
    // a product appearing on two pages, or on neither. `id` is unique, so
    // appending it as a secondary sort pins the order down regardless of
    // ties or plan choice. Asserted on the executed SQL rather than by
    // trying to force page overlap through data, since the underlying
    // instability is a planner/timing property Postgres doesn't guarantee
    // to reproduce for a small, single-session test table.
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'sql-order-check']);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->getJson('/api/v1/products')->assertOk();

    $selectQueries = array_filter(
        $queries,
        fn (string $sql): bool => str_starts_with($sql, 'select') && str_contains($sql, 'order by'),
    );

    expect($selectQueries)->not->toBeEmpty()->each->toMatch('/order by "\w+" (asc|desc), "id" asc/');
});

test('sku returns an exact match regardless of default pagination and sort order', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();

    foreach (range(1, 8) as $i) {
        createProduct($category, ['sku' => "zzz-page-test-{$i}", 'name' => "Zzz Page Test {$i}"]);
    }
    createProduct($category, ['sku' => 'target-sku', 'name' => 'Aaa Not On Page One']);

    $response = $this->getJson('/api/v1/products?sku=target-sku');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['target-sku']);
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

test('facets with no filters reflect brand and price_range across every category, plus per-key attributes for the resolved category', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $matching = createCategory(['slug' => 'matching-category', 'filterable_attributes' => ['amperage']]);
    $other = createCategory(['slug' => 'other-category']);

    createProduct($matching, ['sku' => 'a', 'brand' => 'EMAT', 'price_cents' => 100, 'attributes' => ['amperage' => '16A']]);
    createProduct($matching, ['sku' => 'b', 'brand' => 'ABB', 'price_cents' => 900, 'attributes' => ['amperage' => '40A']]);
    createProduct($other, ['sku' => 'c', 'brand' => 'Legrand', 'price_cents' => 500]);

    $response = $this->getJson('/api/v1/facets');

    $response->assertOk()
        ->assertJson([
            'brand' => ['ABB', 'EMAT', 'Legrand'],
            'price_range' => ['min' => 100, 'max' => 900],
            'attributes' => [],
        ]);

    $withCategory = $this->getJson('/api/v1/facets?category=matching-category');

    $withCategory->assertOk()
        ->assertJson([
            'brand' => ['ABB', 'EMAT'],
            'price_range' => ['min' => 100, 'max' => 900],
            'attributes' => ['amperage' => ['16A', '40A']],
        ]);
});

test('selecting an attribute value narrows facets.brand down to only the brands that still reach it, compounding rather than just flagging unavailable', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['slug' => 'narrowing-category', 'filterable_attributes' => ['amperage']]);

    createProduct($category, ['sku' => 'emat-16a', 'brand' => 'EMAT', 'attributes' => ['amperage' => '16A']]);
    createProduct($category, ['sku' => 'abb-40a', 'brand' => 'ABB', 'attributes' => ['amperage' => '40A']]);
    createProduct($category, ['sku' => 'legrand-40a', 'brand' => 'Legrand', 'attributes' => ['amperage' => '40A']]);

    $response = $this->getJson('/api/v1/facets?category=narrowing-category&amperage=40A');

    $response->assertOk()
        ->assertJson([
            'brand' => ['ABB', 'Legrand'],
        ]);
});

test('selecting brand narrows the other facets but not brand itself (exclude-self)', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['slug' => 'exclude-self-category', 'filterable_attributes' => ['amperage']]);

    createProduct($category, ['sku' => 'abb-16a', 'brand' => 'ABB', 'attributes' => ['amperage' => '16A']]);
    createProduct($category, ['sku' => 'abb-40a', 'brand' => 'ABB', 'attributes' => ['amperage' => '40A']]);
    createProduct($category, ['sku' => 'emat-63a', 'brand' => 'EMAT', 'attributes' => ['amperage' => '63A']]);

    $response = $this->getJson('/api/v1/facets?category=exclude-self-category&brand=ABB');

    $response->assertOk()
        ->assertJson([
            'brand' => ['ABB', 'EMAT'],
            'attributes' => ['amperage' => ['16A', '40A']],
        ]);
});

test('selecting a brand filter narrows facets.category down to only the categories that still have a matching product', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $groepenkast = createCategory(['slug' => 'groepenkast-componenten']);
    $installatiemateriaal = createCategory(['slug' => 'installatiemateriaal']);
    $kabelsDraden = createCategory(['slug' => 'kabels-draden']);

    createProduct($groepenkast, ['sku' => 'abb-1', 'brand' => 'ABB']);
    createProduct($installatiemateriaal, ['sku' => 'other-1', 'brand' => 'EMAT']);
    createProduct($kabelsDraden, ['sku' => 'other-2', 'brand' => 'Nexans']);

    $response = $this->getJson('/api/v1/facets?brand=ABB');

    $response->assertOk()
        ->assertJson([
            'category' => ['groepenkast-componenten'],
        ]);
});

test('facets.category still lists every reachable category when no other filter narrows it', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $first = createCategory(['slug' => 'category-a']);
    $second = createCategory(['slug' => 'category-b']);

    createProduct($first, ['sku' => 'a-1']);
    createProduct($second, ['sku' => 'b-1']);

    $response = $this->getJson('/api/v1/facets');

    $response->assertOk()
        ->assertJson([
            'category' => ['category-a', 'category-b'],
        ]);
});

test('selecting a category does not narrow facets.category to just itself (exclude-self)', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $selected = createCategory(['slug' => 'selected-category']);
    $other = createCategory(['slug' => 'other-category']);

    createProduct($selected, ['sku' => 'selected-1', 'brand' => 'SharedBrand']);
    createProduct($other, ['sku' => 'other-1', 'brand' => 'SharedBrand']);

    $response = $this->getJson('/api/v1/facets?category=selected-category');

    $response->assertOk()
        ->assertJson([
            'category' => ['other-category', 'selected-category'],
        ]);
});

test('a category-specific attribute filter never leaks into facets.category, since it has no meaning outside the selected category', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $withAttribute = createCategory(['slug' => 'with-attribute', 'filterable_attributes' => ['amperage']]);
    $withoutAttribute = createCategory(['slug' => 'without-attribute']);

    createProduct($withAttribute, ['sku' => 'a-16a', 'attributes' => ['amperage' => '16A']]);
    createProduct($withoutAttribute, ['sku' => 'b-1', 'attributes' => []]);

    // If `amperage=16A` were forwarded into the category-facet query as-is,
    // `without-attribute`'s product (which has no "amperage" key at all)
    // would wrongly be filtered out, even though the attribute filter was
    // never meant to apply there in the first place.
    $response = $this->getJson('/api/v1/facets?category=with-attribute&amperage=16A');

    $response->assertOk()
        ->assertJson([
            'category' => ['with-attribute', 'without-attribute'],
        ]);
});

test('a draft product\'s brand/attribute values never appear in facets when status=published is requested, the same status the BFF always sends', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['slug' => 'draft-leak-category', 'filterable_attributes' => ['amperage']]);

    createProduct($category, ['sku' => 'published-1', 'brand' => 'RealBrand', 'attributes' => ['amperage' => '16A']]);
    createProduct($category, [
        'sku' => 'draft-1',
        'brand' => 'DraftOnlyBrand',
        'attributes' => ['amperage' => '999A'],
        'status' => 'draft',
    ]);

    $response = $this->getJson('/api/v1/facets?category=draft-leak-category&status=published');

    $response->assertOk()
        ->assertJson([
            'brand' => ['RealBrand'],
            'attributes' => ['amperage' => ['16A']],
        ]);
});

test('facets with no status filter still includes every status, matching ListProductsAction\'s own default', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['slug' => 'no-status-filter-category']);

    createProduct($category, ['sku' => 'published-2', 'brand' => 'RealBrand']);
    createProduct($category, ['sku' => 'draft-2', 'brand' => 'DraftBrand', 'status' => 'draft']);

    $response = $this->getJson('/api/v1/facets?category=no-status-filter-category');

    $response->assertOk()
        ->assertJson([
            'brand' => ['DraftBrand', 'RealBrand'],
        ]);
});

test('facets with no category selected returns empty attributes but populated brand and price_range', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory(['filterable_attributes' => ['amperage']]);
    createProduct($category, ['sku' => 'a', 'brand' => 'EMAT', 'price_cents' => 250]);

    $response = $this->getJson('/api/v1/facets');

    $response->assertOk()
        ->assertJson([
            'brand' => ['EMAT'],
            'price_range' => ['min' => 250, 'max' => 250],
            'attributes' => [],
        ]);
});

test('facets for an unknown category slug returns the same zero-results shape as the products filter', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);
    $category = createCategory();
    createProduct($category, ['sku' => 'irrelevant']);

    $response = $this->getJson('/api/v1/facets?category=does-not-exist');

    $response->assertOk()
        ->assertJson([
            'brand' => [],
            'price_range' => ['min' => null, 'max' => null],
            'attributes' => [],
        ]);
});

test('rejects invalid facets query values', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);

    $response = $this->getJson('/api/v1/facets?price_min=-1&price_max=abc');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['price_min', 'price_max']);
});

test('rejects a facets price range where the minimum exceeds the maximum', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['catalog:read']);

    $response = $this->getJson('/api/v1/facets?price_min=200&price_max=100');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['price_min', 'price_max']);
});

test('rejects a facets call whose token lacks the catalog:read ability', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['some:other-ability']);
    createCategory();

    $response = $this->getJson('/api/v1/facets');

    $response->assertForbidden();
});

test('rejects an unauthenticated facets call', function (): void {
    $response = $this->getJson('/api/v1/facets');

    $response->assertUnauthorized();
});
