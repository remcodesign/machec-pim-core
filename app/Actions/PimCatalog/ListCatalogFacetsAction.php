<?php

namespace App\Actions\PimCatalog;

use App\Data\Responses\CatalogFacetsData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Exclude-self faceting: for `brand`, `category`, `price_range`, and each
 * of the resolved category's own `filterable_attributes` keys, computes
 * the values still reachable by applying every *other* currently-selected
 * filter through the shared `Product::filter()` scope, but never a
 * field's own current value — the same pattern
 * `ProductIndex::priceRange()` already uses for one field, generalized
 * here to `brand`, `category`, and every category attribute key too.
 */
class ListCatalogFacetsAction
{
    public function handle(Request $request): CatalogFacetsData
    {
        $resolvedCategory = $request->filled('category')
            ? Category::where('slug', $request->query('category'))->first()
            : null;

        $attributeKeys = $resolvedCategory instanceof Category ? $resolvedCategory->filterable_attributes : [];

        $baseFilters = array_filter(
            [
                'category' => $request->query('category'),
                'brand' => $request->query('brand'),
                'price_min' => $request->query('price_min'),
                'price_max' => $request->query('price_max'),
                ...array_combine($attributeKeys, array_map(
                    fn (string $key) => $request->query($key),
                    $attributeKeys,
                )),
            ],
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        /** @var list<string> $brand */
        $brand = Product::query()
            ->filter(Request::create('/', 'GET', collect($baseFilters)->except('brand')->all()))
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values()
            ->all();

        $priceQuery = Product::query()
            ->filter(Request::create('/', 'GET', collect($baseFilters)->except(['price_min', 'price_max'])->all()));

        $min = (clone $priceQuery)->min('price_cents');
        $max = (clone $priceQuery)->max('price_cents');

        // Attribute keys are dropped here too, not just `category` itself —
        // they're scoped to the *selected* category's own schema
        // (Category::filterable_attributes), so they have no meaning once
        // category is excluded to ask "what other categories are still
        // reachable." Product::filter() would silently ignore them anyway
        // once `category` is absent from the request (it only applies
        // attribute where-clauses when a category resolves), but dropping
        // them here keeps that reasoning explicit rather than incidental.
        /** @var list<string> $categorySlugs */
        $categorySlugs = Product::query()
            ->with('category:id,slug')
            ->filter(Request::create('/', 'GET', collect($baseFilters)->except(['category', ...$attributeKeys])->all()))
            ->get()
            ->pluck('category.slug')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        /** @var array<string, list<string>> $attributes */
        $attributes = [];

        foreach ($attributeKeys as $attributeKey) {
            /** @var list<string> $values */
            $values = Product::query()
                ->filter(Request::create('/', 'GET', collect($baseFilters)->except($attributeKey)->all()))
                ->pluck('attributes')
                ->pluck($attributeKey)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $attributes[$attributeKey] = $values;
        }

        return new CatalogFacetsData(
            brand: $brand,
            category: $categorySlugs,
            price_range: [
                'min' => $min !== null ? (int) $min : null,
                'max' => $max !== null ? (int) $max : null,
            ],
            attributes: $attributes,
        );
    }
}
