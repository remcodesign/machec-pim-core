<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CatalogProductsRequestData extends Data
{
    public function __construct(
        public ?string $modified_since = null,
        public ?string $sku = null,
        public ?string $category = null,
        public ?string $brand = null,
        public ?int $price_min = null,
        public ?int $price_max = null,
        public ?string $status = null,
        public ?string $sort = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'modified_since' => ['nullable', 'date'],
            'sku' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price_min' => ['nullable', 'integer', 'min:0', 'lte:price_max'],
            'price_max' => ['nullable', 'integer', 'min:0', 'gte:price_min'],
            'status' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
        ];
    }
}
