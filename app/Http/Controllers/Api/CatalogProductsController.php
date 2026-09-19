<?php

namespace App\Http\Controllers\Api;

use App\Actions\PimCatalog\ListProductsAction;
use App\Data\Requests\CatalogProductsRequestData;
use App\Data\Responses\ProductListData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `Api\CatalogReadController` in the master spec covers both catalog-read
 * routes as one Resourceful controller — split here into two single-action
 * invokables (this one and `CatalogCategoriesController`) to follow this
 * app's own committed `.ai/rules/api.md` convention instead.
 */
class CatalogProductsController extends Controller
{
    public function __invoke(
        CatalogProductsRequestData $data,
        Request $request,
        ListProductsAction $action,
    ): JsonResponse {
        $products = $action->handle($request)->through(
            fn ($product) => ProductListData::fromProduct($product),
        );

        return response()->json($products);
    }
}
