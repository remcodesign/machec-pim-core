<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CatalogCategoriesRequestData extends Data
{
    public function __construct(
        public ?string $modified_since = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'modified_since' => ['nullable', 'date'],
        ];
    }
}
