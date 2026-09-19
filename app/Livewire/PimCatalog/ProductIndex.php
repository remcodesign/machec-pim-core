<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\ListProductsAction;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reuses `ListProductsAction` — the exact same filter/sort/pagination
 * engine the storefront's own read API calls (Step 3.3) — so this admin
 * listing never drifts from what a shopper on the BFF actually sees
 * (D67/D68/D75/D111).
 */
#[Layout('layouts.app')]
#[Title('Products')]
class ProductIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $category = '';

    #[Url]
    public string $brand = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $price_min = '';

    #[Url]
    public string $price_max = '';

    #[Url]
    public string $sort = 'name_asc';

    /**
     * Typed `mixed`, not `string` — this is hydrated from client-
     * controlled query-string input (`#[Url]`) and a crafted/malformed
     * URL can put a non-string value here before `mount()` sanitizes it.
     *
     * @var array<string, mixed>
     */
    #[Url]
    public array $attributeFilters = [];

    /**
     * `attributeFilters` is hydrated from client-controlled query-string
     * input (`#[Url]`) — a malformed/crafted URL can put a non-string
     * (even `null`) value in there. Dropping those here, right after
     * Livewire's own URL hydration and before any Computed property
     * touches the array, keeps every filter shown/applied a real string,
     * the same "bad filter input is ignored, never a 500" posture the
     * read API already has (see the unrecognized-attribute-key test).
     */
    public function mount(): void
    {
        $this->attributeFilters = array_filter(
            $this->attributeFilters,
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );
    }

    public function updating(string $name, mixed $value): void
    {
        if (in_array($name, ['category', 'brand', 'status', 'price_min', 'price_max', 'sort'], true)
            || str_starts_with($name, 'attributeFilters')) {
            $this->resetPage();
        }
    }

    /**
     * Drops attribute filters that don't belong to the newly selected
     * category — the same "never offer a value that returns zero
     * results" guardrail D68's storefront panel already enforces.
     */
    public function updatedCategory(): void
    {
        $category = $this->selectedCategory();
        $allowed = $category instanceof Category ? $category->filterable_attributes : [];
        $this->attributeFilters = array_intersect_key($this->attributeFilters, array_flip($allowed));
    }

    public function clearFilters(): void
    {
        $this->reset(['category', 'brand', 'status', 'price_min', 'price_max', 'sort', 'attributeFilters']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->category !== ''
            || $this->brand !== ''
            || $this->status !== ''
            || $this->price_min !== ''
            || $this->price_max !== ''
            || $this->sort !== 'name_asc'
            || $this->attributeFilters !== [];
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
     * `brand` stays a plain string column (D67 — no `pim_brands` table
     * yet, see `docs_local/specs-z-future.md` for the deferred normalized
     * version) — distinct values already in the data, so the filter is a
     * real selector, not free text.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function brands(): Collection
    {
        return Product::query()->distinct()->orderBy('brand')->pluck('brand');
    }

    /**
     * @return list<ProductStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return ProductStatus::cases();
    }

    #[Computed]
    public function selectedCategory(): ?Category
    {
        return $this->category !== '' ? Category::where('slug', $this->category)->first() : null;
    }

    /**
     * Min/max price across the current, unpaginated, filtered-by-
     * everything-except-price result set — shown as a hint next to the
     * price inputs so an admin knows what range is actually there to
     * filter, instead of guessing. Deliberately excludes `price_min`/
     * `price_max` themselves, so typing a bound doesn't shrink the very
     * range it's being compared against.
     *
     * @return array{min: int|null, max: int|null}
     */
    #[Computed]
    public function priceRange(): array
    {
        $filters = array_filter(
            [
                'category' => $this->category,
                'brand' => $this->brand,
                'status' => $this->status,
                ...$this->attributeFilters,
            ],
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        $query = Product::query()->filter(Request::create('/', 'GET', $filters));

        $min = (clone $query)->min('price_cents');
        $max = (clone $query)->max('price_cents');

        return [
            'min' => $min !== null ? (int) $min : null,
            'max' => $max !== null ? (int) $max : null,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = array_filter(
            [
                'category' => $this->category,
                'brand' => $this->brand,
                'status' => $this->status,
                'price_min' => $this->price_min,
                'price_max' => $this->price_max,
                'sort' => $this->sort,
                ...$this->attributeFilters,
            ],
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        return app(ListProductsAction::class)->handle(Request::create('/', 'GET', $query));
    }

    public function render(): View
    {
        return view('livewire.product-index');
    }
}
