<?php

namespace App\Data\Requests;

use App\Data\Casts\MoneyCast;
use App\Data\Casts\SkuCast;
use App\Enums\ProductStatus;
use App\ValueObjects\Money;
use App\ValueObjects\Sku;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

/**
 * CURRENTLY UNUSED, DEAD CODE — same situation as `SaveProductAction`,
 * its only caller (`Api\AdminProductController`) was removed for having
 * no real consumer anywhere in the system. Kept for Step 3.5's
 * `ProductForm`, which is spec'd to construct this exact DTO. Remove this
 * note once Step 3.5 wires it up and adds real test coverage.
 */
class ProductData extends Data
{
    /**
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        #[WithCast(SkuCast::class)]
        public Sku $sku,
        public string $name,
        public string $brand,
        #[MapInputName('price_cents'), WithCast(MoneyCast::class)]
        public Money $price,
        public string $category_slug,
        public ProductStatus $status,
        public array $attributes,
    ) {}
}
