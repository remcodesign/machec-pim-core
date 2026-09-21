<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CatalogFacetsRequestData extends Data
{
    public function __construct(
        public ?string $category = null,
        public ?string $brand = null,
        public ?int $price_min = null,
        public ?int $price_max = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(?ValidationContext $context = null): array
    {
        $payload = $context === null ? [] : ($context->payload ?? []);

        // Same "only cross-validate once both bounds are present" shape as
        // CatalogProductsRequestData — lte/gte fail closed when the field
        // they compare against is missing entirely, not just when it fails
        // the comparison.
        $hasPriceMin = filled($payload['price_min'] ?? null);
        $hasPriceMax = filled($payload['price_max'] ?? null);

        return [
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price_min' => array_filter([
                'nullable', 'integer', 'min:0',
                $hasPriceMax ? 'lte:price_max' : null,
            ]),
            'price_max' => array_filter([
                'nullable', 'integer', 'min:0',
                $hasPriceMin ? 'gte:price_min' : null,
            ]),
        ];
    }
}
