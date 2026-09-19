<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Deletes one attribute key from a category's own `filterable_attributes`
 * and strips that same key out of every one of that category's products'
 * `attributes` JSON, atomically — an immediate, confirmed action from
 * `CategoryForm`, never deferred to the general "Save" button, so the key
 * never becomes silently orphaned data on products with no field left to
 * edit it through.
 */
class RemoveCategoryAttributeAction
{
    use WritesAuditLog;

    public function handle(Request $request, User $admin, Category $category, string $attributeKey): Category
    {
        // Re-checked here, never trusted from hidden UI alone (D97's pattern).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        DB::transaction(function () use ($request, $admin, $category, $attributeKey): void {
            $category->update([
                'filterable_attributes' => array_values(array_diff($category->filterable_attributes, [$attributeKey])),
            ]);

            foreach ($category->products()->get() as $product) {
                if (array_key_exists($attributeKey, $product->attributes)) {
                    $attributes = $product->attributes;
                    // Remove the attribute key from the product's attributes array.
                    unset($attributes[$attributeKey]);
                    // Persist the updated attributes array back to the product.
                    $product->update(['attributes' => $attributes]);
                }
            }

            $this->recordAuditLog($request, $admin, 'category.attribute_removed', $category);
        });

        return $category->refresh();
    }
}
