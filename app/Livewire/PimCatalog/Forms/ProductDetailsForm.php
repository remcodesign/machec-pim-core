<?php

namespace App\Livewire\PimCatalog\Forms;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProductDetailsForm extends Form
{
    public ?Product $product = null;

    public string $sku = '';

    public string $name = '';

    public string $brand = '';

    public string $price = '';

    public string $category_slug = '';

    public string $status = '';

    /**
     * @var array<string, string>
     */
    public array $attributes = [];

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
        $this->sku = $product->sku ?? '';
        $this->name = $product->name ?? '';
        $this->brand = $product->brand ?? '';
        $this->price = $product instanceof Product ? number_format($product->price_cents / 100, 2, '.', '') : '';
        $this->category_slug = $product?->category->slug ?? '';
        $this->status = $product instanceof Product ? $product->status->value : ProductStatus::Draft->value;
        $this->attributes = $product->attributes ?? [];
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    protected function rules(): array
    {
        return [
            'sku' => [
                'required', 'string', 'max:255',
                Rule::unique('pim_products', 'sku')->ignore($this->product?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_slug' => ['required', 'string', Rule::exists('pim_categories', 'slug')],
            'status' => ['required', 'string', 'in:'.implode(',', array_map(
                fn (ProductStatus $status): string => $status->value,
                ProductStatus::cases(),
            ))],
            'attributes' => ['array'],
            'attributes.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
