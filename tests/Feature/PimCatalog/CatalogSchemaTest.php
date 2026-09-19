<?php

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Vector;

test('creates pim_products with a pgvector embedding column', function (): void {
    $category = Category::create([
        'slug' => 'test-category',
        'name' => 'Test Category',
        'filterable_attributes' => ['amperage'],
    ]);

    $product = Product::create([
        'sku' => 'test-sku-1',
        'category_id' => $category->id,
        'name' => 'Test Product',
        'brand' => 'Test Brand',
        'price_cents' => 1000,
        'status' => 'draft',
        'attributes' => ['amperage' => '16A'],
        'embedding' => array_fill(0, 1536, 0.0),
    ]);

    $product->refresh();

    expect($product->embedding)->toBeInstanceOf(Vector::class)
        ->and($product->embedding->toArray())->toHaveCount(1536);
});

test('CatalogSeeder persists every category and product from its own seed data', function (): void {
    $this->seed(CatalogSeeder::class);

    expect(Category::count())->toBe(count(CatalogSeeder::CATEGORIES))
        ->and(Product::count())->toBe(count(CatalogSeeder::PRODUCTS));

    foreach (CatalogSeeder::CATEGORIES as $expected) {
        $category = Category::where('slug', $expected['slug'])->firstOrFail();

        expect($category->name)->toBe($expected['name'])
            ->and($category->filterable_attributes)->toBe(array_keys($expected['filters']));
    }

    foreach (CatalogSeeder::PRODUCTS as $expected) {
        $product = Product::where('sku', $expected['sku'])->firstOrFail();

        // jsonb does not preserve key insertion order, so compare regardless of order.
        expect($product->brand)->toBe($expected['brand'])
            ->and($product->getAttribute('attributes'))->toEqualCanonicalizing($expected['attributes'])
            ->and($product->category->slug)->toBe($expected['category_slug']);
    }
});

test('rejects a product insert with a negative stock value', function (): void {
    $category = Category::create([
        'slug' => 'test-category',
        'name' => 'Test Category',
        'filterable_attributes' => [],
    ]);

    DB::table('pim_products')->insert([
        'sku' => 'test-sku-negative-stock',
        'category_id' => $category->id,
        'name' => 'Test Product',
        'brand' => 'Test Brand',
        'price_cents' => 1000,
        'stock' => -1,
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);

test('rejects a second pim_stock_ledger row for the same reference and sku', function (): void {
    DB::table('pim_stock_ledger')->insert([
        'reference' => 'ref-1',
        'sku' => 'test-sku',
        'quantity' => -1,
        'resulting_stock' => 9,
        'created_at' => now(),
    ]);

    DB::table('pim_stock_ledger')->insert([
        'reference' => 'ref-1',
        'sku' => 'test-sku',
        'quantity' => -1,
        'resulting_stock' => 8,
        'created_at' => now(),
    ]);
})->throws(UniqueConstraintViolationException::class);
