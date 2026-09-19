<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int|null $parent_id
 * @property list<string> $filterable_attributes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'pim_categories')]
#[Fillable(['slug', 'name', 'parent_id', 'filterable_attributes'])]
class Category extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filterable_attributes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
