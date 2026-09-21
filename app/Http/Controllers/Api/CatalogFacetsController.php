<?php

namespace App\Http\Controllers\Api;

use App\Actions\PimCatalog\ListCatalogFacetsAction;
use App\Data\Requests\CatalogFacetsRequestData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogFacetsController extends Controller
{
    public function __invoke(
        CatalogFacetsRequestData $data,
        Request $request,
        ListCatalogFacetsAction $action,
    ): JsonResponse {
        return response()->json($action->handle($request));
    }
}
