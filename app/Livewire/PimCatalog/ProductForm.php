<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\SaveProductAction;
use App\Data\Requests\ProductData;
use App\Enums\ProductStatus;
use App\Livewire\PimCatalog\Forms\ProductDetailsForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\ValueObjects\Money;
use App\ValueObjects\Sku;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Product')]
class ProductForm extends Component
{
    public ?Product $product = null;

    public ProductDetailsForm $form;

    public function mount(?Product $product = null): void
    {
        $this->product = $product;
        $this->form->setProduct($product);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * @return list<ProductStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return ProductStatus::cases();
    }

    /**
     * `brand` stays a plain string column (D67 — no `pim_brands` table
     * yet, see `docs_local/specs-z-future.md`) — a `<datalist>` of the
     * distinct brands already in use, so picking an existing one is a
     * real selector while a genuinely new brand can still be typed.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Product::query()->distinct()->orderBy('brand')->pluck('brand');
    }

    #[Computed]
    public function selectedCategory(): ?Category
    {
        return $this->form->category_slug !== ''
            ? Category::where('slug', $this->form->category_slug)->first()
            : null;
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function filterableAttributeKeys(): array
    {
        $category = $this->selectedCategory();

        return $category instanceof Category ? $category->filterable_attributes : [];
    }

    /**
     * Drops attribute values that no longer belong to the newly selected
     * category, mirroring `ProductIndex`'s own filter-reset behavior.
     */
    public function updatedFormCategorySlug(): void
    {
        $allowed = $this->filterableAttributeKeys();
        $this->form->attributes = array_intersect_key($this->form->attributes, array_flip($allowed));
    }

    public function save(SaveProductAction $action): void
    {
        $validated = $this->form->validate();

        /** @var User $admin */
        $admin = Auth::user();

        $data = new ProductData(
            sku: new Sku($validated['sku']),
            name: $validated['name'],
            brand: $validated['brand'],
            price: new Money((int) round(((float) $validated['price']) * 100)),
            category_slug: $validated['category_slug'],
            status: ProductStatus::from($validated['status']),
            attributes: array_filter(
                $validated['attributes'],
                fn (?string $value): bool => $value !== null && $value !== '',
            ),
        );

        $action->handle(request(), $admin, $data, $this->product);

        $this->redirect(route('products.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.product-form');
    }
}
