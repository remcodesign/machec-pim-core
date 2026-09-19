<?php

namespace App\Actions\PimCatalog;

use App\Data\Requests\ProductData;
use App\Models\Category;
use App\Models\Product;
use App\Services\CacheGenerationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveProductAction
{
    public function __construct(private readonly CacheGenerationService $cacheGenerationService) {}

    public function handle(ProductData $data): Product
    {
        Validator::make(
            ['category_slug' => $data->category_slug],
            ['category_slug' => Rule::exists(Category::class, 'slug')],
        )->validate();

        $category = Category::where('slug', $data->category_slug)->firstOrFail();

        // `embedding` is deliberately left unset — it stays null until a real
        // embedding provider populates it (D109); a fabricated vector would
        // be indistinguishable from real data to any later similarity search.
        $product = Product::create([
            'sku' => $data->sku->value,
            'category_id' => $category->id,
            'name' => $data->name,
            'brand' => $data->brand,
            'price_cents' => $data->price->cents,
            'status' => $data->status,
            'attributes' => $data->attributes,
        ]);

        // Invalidates every previously-cached pim_products key (D14) — the read side (Step 3.5) hasn't landed yet.
        $this->cacheGenerationService->bump('pim_products');

        return $product;
    }
}
