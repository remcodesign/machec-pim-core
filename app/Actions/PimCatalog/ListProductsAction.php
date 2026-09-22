<?php

namespace App\Actions\PimCatalog;

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
        // Start building the query for products, including their category and applying any filters from the request.
        $query = Product::query()->with('category')->filter($request);

        [$column, $direction] = self::SORT_COLUMNS[$request->query('sort')] ?? self::SORT_COLUMNS['name_asc'];
        // `id` breaks ties deterministically. Without it, `paginate()`'s two
        // separate LIMIT/OFFSET queries (one per page) aren't guaranteed to
        // agree on tied rows' relative order — Postgres can resolve the
        // primary column differently between them (a different scan plan,
        // stats refreshed in between), which surfaces as the same product
        // appearing on two pages or vanishing between them.
        $query->orderBy($column, $direction)->orderBy('id', 'asc');

        return $query->paginate(self::PAGE_SIZE);
    }
}
