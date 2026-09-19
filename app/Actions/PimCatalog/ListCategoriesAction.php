<?php

namespace App\Actions\PimCatalog;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCategoriesAction
{
    /**
     * Fixed, documented, never caller-controlled (D75) — same page size
     * as the products endpoint.
     */
    private const int PAGE_SIZE = 6;

    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function handle(Request $request): LengthAwarePaginator
    {
        $query = Category::query();

        if ($request->filled('modified_since')) {
            $query->where('updated_at', '>', $request->date('modified_since'));
        }

        return $query->paginate(self::PAGE_SIZE);
    }
}
