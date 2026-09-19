<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductStatus;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @property int $id
 * @property string $sku
 * @property int $category_id
 * @property string $name
 * @property string $brand
 * @property Money $price
 * @property int $price_cents
 * @property int $stock
 * @property Vector|null $embedding
 * @property array<string, string> $attributes
 * @property ProductStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Table(name: 'pim_products')]
#[Fillable(['sku', 'category_id', 'name', 'brand', 'price_cents', 'stock', 'embedding', 'attributes', 'status'])]
class Product extends Model
{
    use HasNeighbors;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'embedding' => Vector::class,
            'attributes' => 'array',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * `modified_since`/`category`/`brand`/`price_min`/`price_max`/`status`/
     * a category's own `filterable_attributes` — the one, shared filter
     * shape both the storefront's read API (`ListProductsAction`, Step
     * 3.3) and the admin's `ProductIndex` (D111) query against, so
     * neither ever drifts from the other. `status` is an admin-only need
     * in practice (the storefront has no reason to ask for drafts) but
     * costs nothing extra to keep here rather than forking the filter
     * logic in two places.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function filter(Builder $query, Request $request): void
    {
        if ($request->filled('modified_since')) {
            $query->where('updated_at', '>', $request->date('modified_since'));
        }

        $category = null;

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->query('category'))->first();

            // An unknown category slug filters to zero results rather than
            // silently ignoring the filter and returning every product.
            $query->where('category_id', $category === null ? 0 : $category->id);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->query('brand'));
        }

        if ($request->filled('price_min')) {
            $query->where('price_cents', '>=', (int) $request->query('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price_cents', '<=', (int) $request->query('price_max'));
        }

        if ($request->filled('status')) {
            $status = ProductStatus::tryFrom((string) $request->query('status'));

            // An unrecognized status is silently ignored, not a 422 — same
            // shape as an unrecognized attribute key below.
            if ($status instanceof ProductStatus) {
                $query->where('status', $status);
            }
        }

        if ($category instanceof Category) {
            foreach ($category->filterable_attributes as $attributeKey) {
                if ($request->filled($attributeKey)) {
                    $query->where("attributes->{$attributeKey}", $request->query($attributeKey));
                }
            }
        }
    }
}
