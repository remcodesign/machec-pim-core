<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * `CategoryForm`'s only caller — same in-process shape as
 * `SaveProductAction`/`ProductForm` (D110), no controller/route.
 */
class SaveCategoryAction
{
    use WritesAuditLog;

    /**
     * @param  array{slug: string, name: string, parent_id: int|null, filterable_attributes: list<string>}  $data
     * @param  array<string, string>  $attributeRenames  Old key => new key, for lines whose text changed since this category was last saved (never a removed/added line — those are add-on-save/no-op and confirmed-delete respectively).
     */
    public function handle(Request $request, User $admin, array $data, ?Category $category = null, array $attributeRenames = []): Category
    {
        // Re-checked here, never trusted from hidden UI alone (D97's pattern).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        return DB::transaction(function () use ($request, $admin, $data, $category, $attributeRenames): Category {
            if ($category instanceof Category) {
                $category->update($data);
                $this->recordAuditLog($request, $admin, 'category.updated', $category);
            } else {
                $category = Category::create($data);
                $this->recordAuditLog($request, $admin, 'category.created', $category);
            }

            foreach ($category->products()->get() as $product) {
                $attributes = $product->attributes;
                $changed = false;

                foreach ($attributeRenames as $oldKey => $newKey) {
                    if (array_key_exists($oldKey, $attributes)) {
                        // Rename the attribute key in the product's attributes array.
                        $attributes[$newKey] = $attributes[$oldKey];
                        // Remove the old attribute key from the product's attributes array.
                        unset($attributes[$oldKey]);
                        $changed = true;
                    }
                }

                if ($changed) {
                    $product->update(['attributes' => $attributes]);
                }
            }

            return $category;
        });
    }
}
