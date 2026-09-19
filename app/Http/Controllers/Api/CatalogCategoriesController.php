<?php

namespace App\Http\Controllers\Api;

use App\Actions\PimCatalog\ListCategoriesAction;
use App\Data\Requests\CatalogCategoriesRequestData;
use App\Data\Responses\CategoryListData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogCategoriesController extends Controller
{
    public function __invoke(
        CatalogCategoriesRequestData $data,
        Request $request,
        ListCategoriesAction $action,
    ): JsonResponse {
        $categories = $action->handle($request)->through(
            fn ($category) => CategoryListData::fromCategory($category),
        );

        return response()->json($categories);
    }
}
