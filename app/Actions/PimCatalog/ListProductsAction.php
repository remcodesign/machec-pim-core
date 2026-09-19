<?php

namespace App\Actions\PimCatalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProductsAction
{
    /**
     * Fixed, documented, never caller-controlled (D75).
     */
    private const int PAGE_SIZE = 6;

    /**
     * Fixed column allow-list — `sort` never reaches the query as a raw
     * caller-supplied column/direction string (D75).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array SORT_COLUMNS = [
        'name_asc' => ['name', 'asc'],
        'name_desc' => ['name', 'desc'],
        'price_asc' => ['price_cents', 'asc'],
        'price_desc' => ['price_cents', 'desc'],
    ];

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(Request $request): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

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

        if ($category !== null) {
            foreach ($category->filterable_attributes as $attributeKey) {
                if ($request->filled($attributeKey)) {
                    $query->where("attributes->{$attributeKey}", $request->query($attributeKey));
                }
            }
        }

        [$column, $direction] = self::SORT_COLUMNS[$request->query('sort')] ?? self::SORT_COLUMNS['name_asc'];
        $query->orderBy($column, $direction);

        return $query->paginate(self::PAGE_SIZE);
    }
}
