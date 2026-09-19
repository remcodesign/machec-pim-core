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
