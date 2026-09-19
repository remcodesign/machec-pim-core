<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

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
