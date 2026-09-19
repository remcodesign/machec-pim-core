<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    /**
     * @param  list<string>  $filterable_attributes
     */
    public function __construct(
        public string $slug,
        public string $name,
        public ?int $parent_id,
        public array $filterable_attributes,
    ) {}
}
