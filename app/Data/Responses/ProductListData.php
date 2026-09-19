<?php

namespace App\Data\Responses;

use App\Models\Product;
use Spatie\LaravelData\Data;

/**
 * One product row as it appears in `GET /api/v1/products` (D40). Whatever
 * columns `pim_products` carries ride along automatically once Domain 11's
 * image work lands (`image_path`/`image_master_path`, Post-V1, D28/D31) —
 * no endpoint change needed for that, just a new property here.
 */
class ProductListData extends Data
{
    /**
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        public string $sku,
        public string $name,
        public string $brand,
        public int $price_cents,
        public int $stock,
        public string $category_slug,
        public string $status,
        public array $attributes,
    ) {}

    public static function fromProduct(Product $product): self
    {
        return new self(
            sku: $product->sku,
            name: $product->name,
            brand: $product->brand,
            price_cents: $product->price->cents,
            stock: $product->stock,
            category_slug: $product->category->slug,
            status: $product->status->value,
            attributes: $product->getAttribute('attributes'),
        );
    }
}
