<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Arr;

function touchProductsCategory(): Category
{
    return Category::create([
        'slug' => 'touch-products-category',
        'name' => 'Touch Products Category',
        'filterable_attributes' => [],
    ]);
}

function touchProductsProduct(Category $category, int $number): Product
{
    return Product::create([
        'sku' => "touch-product-{$number}",
        'category_id' => $category->id,
        'name' => "Touch Product {$number}",
        'brand' => 'Test Brand',
        'price_cents' => 1000 + $number,
        'status' => 'published',
        'attributes' => [],
    ]);
}

test('touching 5 products updates only their updated_at column, nothing else', function (): void {
    $this->travelTo('2026-01-01 00:00:00');
    $category = touchProductsCategory();
    $products = collect(range(1, 6))->map(
        fn (int $number): Product => touchProductsProduct($category, $number),
    );
    $products->each(fn (Product $product): Product => $product->refresh());
    $before = $products->mapWithKeys(fn (Product $product): array => [
        $product->id => $product->getAttributes(),
    ]);

    $this->travelTo('2026-01-01 00:01:00');

    $this->artisan('pim:touch-products')
        ->assertSuccessful();

    $products->each(fn (Product $product): Product => $product->refresh());
    $touchedProducts = $products->filter(
        fn (Product $product): bool => $product->updated_at?->toDateTimeString() === '2026-01-01 00:01:00',
    );

    expect($touchedProducts)->toHaveCount(5);

    $products->each(function (Product $product) use ($before): void {
        expect(Arr::except($product->getAttributes(), ['updated_at']))
            ->toEqual(Arr::except($before[$product->id], ['updated_at']));
    });
});

test('touching --all updates every product\'s updated_at column', function (): void {
    $this->travelTo('2026-01-01 00:00:00');
    $category = touchProductsCategory();
    $products = collect(range(1, 3))->map(
        fn (int $number): Product => touchProductsProduct($category, $number),
    );
    $products->each(fn (Product $product): Product => $product->refresh());
    $before = $products->mapWithKeys(fn (Product $product): array => [
        $product->id => $product->getAttributes(),
    ]);

    $this->travelTo('2026-01-01 00:01:00');

    $this->artisan('pim:touch-products', ['--all' => true])
        ->assertSuccessful();

    $products->each(fn (Product $product): Product => $product->refresh());

    expect($products->filter(
        fn (Product $product): bool => $product->updated_at?->toDateTimeString() === '2026-01-01 00:01:00',
    ))->toHaveCount(3);

    $products->each(function (Product $product) use ($before): void {
        expect(Arr::except($product->getAttributes(), ['updated_at']))
            ->toEqual(Arr::except($before[$product->id], ['updated_at']));
    });
});
