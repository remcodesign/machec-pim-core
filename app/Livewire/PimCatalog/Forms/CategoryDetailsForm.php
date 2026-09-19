<?php

namespace App\Livewire\PimCatalog\Forms;

use App\Models\Category;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CategoryDetailsForm extends Form
{
    public ?Category $category = null;

    public string $slug = '';

    public string $name = '';

    public ?int $parent_id = null;

    /**
     * One attribute key per line — `CategoryForm::addAttributeLine()`
     * appends a blank one. Editing an already-persisted line's text is a
     * rename, cascaded across this category's own products on save
     * (`CategoryForm::save()`, keyed by array position, not text).
     * Removing an already-persisted line goes through
     * `CategoryForm::removeAttribute()` instead — an immediate, confirmed
     * action, never just blanking the line here, which the validation
     * rule below refuses to let happen silently.
     *
     * @var list<string>
     */
    public array $filterable_attributes = [];

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
        $this->slug = $category->slug ?? '';
        $this->name = $category->name ?? '';
        $this->parent_id = $category->parent_id ?? null;
        $this->filterable_attributes = $category->filterable_attributes ?? [];
    }

    /**
     * @return array<string, array<int, string|Rule|Closure>>
     */
    protected function rules(): array
    {
        return [
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pim_categories', 'slug')->ignore($this->category?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('pim_categories', 'id'),
                Rule::notIn(array_filter([$this->category?->id])),
            ],
            'filterable_attributes' => [
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $originalKeys = $this->category instanceof Category ? $this->category->filterable_attributes : [];

                    foreach ($originalKeys as $index => $originalKey) {
                        if (trim($value[$index] ?? '') === '') {
                            $fail(__(
                                'Use the delete button to remove ":key", not clearing the field.',
                                ['key' => $originalKey],
                            ));
                        }
                    }
                },
            ],
            'filterable_attributes.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<string>
     */
    public function cleanedFilterableAttributes(): array
    {
        return array_values(array_unique(array_filter(array_map(
            trim(...),
            $this->filterable_attributes,
        ))));
    }

    /**
     * @return array{slug: string, name: string, parent_id: int|null, filterable_attributes: list<string>}
     */
    public function toCategoryAttributes(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'filterable_attributes' => $this->cleanedFilterableAttributes(),
        ];
    }
}
