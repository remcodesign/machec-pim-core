<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductStatus;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
