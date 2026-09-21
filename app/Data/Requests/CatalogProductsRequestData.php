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
        $payload = $context === null ? [] : ($context->payload ?? []);

        // Laravel's lte/gte comparison rules fail closed when the field
        // they compare against isn't present at all in the request — not
        // just when it fails the comparison — so a lone ?price_min=4000
        // with no price_max always 422'd even though there's nothing to
        // compare it against. Only cross-validate once both bounds are
        // actually given; a single bound still gets its own min:0/integer
        // checks either way.
        $hasPriceMin = filled($payload['price_min'] ?? null);
        $hasPriceMax = filled($payload['price_max'] ?? null);

        return [
            'modified_since' => ['nullable', 'date'],
            'sku' => ['nullable', 'string', 'max:255'],
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
            'status' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
        ];
    }
}
