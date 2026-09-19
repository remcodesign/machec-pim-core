<?php

namespace App\Http\Controllers\Api;

use App\Actions\PimCatalog\RecordStockMovementAction;
use App\Data\Requests\StockMovementData;
use App\Exceptions\StockMovementReferenceConflictException;
use App\Exceptions\StockUnavailableException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    public function __invoke(StockMovementData $data, RecordStockMovementAction $action): JsonResponse
    {
        try {
            return response()->json($action->handle($data));
        } catch (StockMovementReferenceConflictException) {
            return response()->json(['reason' => 'REFERENCE_CONFLICT'], 409);
        } catch (StockUnavailableException) {
            return response()->json(['reason' => 'STOCK_UNAVAILABLE'], 409);
        }
    }
}
