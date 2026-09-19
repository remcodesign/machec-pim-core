<?php

namespace App\Data\Responses;

use App\Models\Category;
use Spatie\LaravelData\Data;

class CategoryListData extends Data
{
    /**
     * @param  list<string>  $filterable_attributes
     */
    public function __construct(
        public string $slug,
        public string $name,
        public array $filterable_attributes,
    ) {}

    public static function fromCategory(Category $category): self
    {
        return new self(
            slug: $category->slug,
            name: $category->name,
            filterable_attributes: $category->filterable_attributes,
        );
    }
}
