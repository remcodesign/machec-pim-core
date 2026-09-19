<?php

namespace App\Http\Controllers\Api;

use App\Actions\PimCatalog\SaveProductAction;
use App\Data\Requests\ProductData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdminProductController extends Controller
{
    public function __invoke(ProductData $data, SaveProductAction $action): JsonResponse
    {
        $product = $action->handle($data);

        return response()->json([
            'id' => $product->id,
            'sku' => $product->sku,
        ], 201);
    }
}
