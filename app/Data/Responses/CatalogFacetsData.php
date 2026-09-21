<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Data;

/**
 * Exclude-self faceting for `GET /api/v1/facets` — for `brand`, `category`,
 * `price_range`, and each of the resolved category's own
 * `filterable_attributes` keys, the values still reachable once every
 * *other* currently-selected filter is applied, but never a field's own
 * current value. `GET /api/v1/categories` (the nav-tree taxonomy) is
 * unaffected by this — `category` here is a narrower, filter-aware slug
 * list a caller can intersect with that full taxonomy client-side, not a
 * replacement for it.
 */
class CatalogFacetsData extends Data
{
    /**
     * @param  list<string>  $brand
     * @param  list<string>  $category
     * @param  array{min: int|null, max: int|null}  $price_range
     * @param  array<string, list<string>>  $attributes
     */
    public function __construct(
        public array $brand,
        public array $category,
        public array $price_range,
        public array $attributes,
    ) {}
}
