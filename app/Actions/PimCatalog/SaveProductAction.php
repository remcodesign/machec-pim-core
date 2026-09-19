<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Data\Requests\ProductData;
use App\Enums\PimRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * `ProductForm`'s only caller (D110) — called in-process, no controller.
 */
class SaveProductAction
{
    use WritesAuditLog;

    public function handle(Request $request, User $admin, ProductData $data, ?Product $product = null): Product
    {
        // Re-checked here, never trusted from hidden UI alone (D97's pattern).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        Validator::make(
            ['category_slug' => $data->category_slug],
            ['category_slug' => Rule::exists(Category::class, 'slug')],
        )->validate();

        $category = Category::where('slug', $data->category_slug)->firstOrFail();

        $attributes = [
            'sku' => $data->sku->value,
            'category_id' => $category->id,
            'name' => $data->name,
            'brand' => $data->brand,
            'price_cents' => $data->price->cents,
            'status' => $data->status,
            'attributes' => $data->attributes,
        ];

        if ($product instanceof Product) {
            $product->update($attributes);
            $this->recordAuditLog($request, $admin, 'product.updated', $product);
        } else {
            // `embedding` is deliberately left unset — it stays null until a real
            // embedding provider populates it (D109); a fabricated vector would
            // be indistinguishable from real data to any later similarity search.
            $product = Product::create($attributes);
            $this->recordAuditLog($request, $admin, 'product.created', $product);
        }

        return $product;
    }
}
