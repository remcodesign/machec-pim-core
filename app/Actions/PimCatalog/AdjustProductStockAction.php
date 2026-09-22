<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Data\Requests\StockMovementData;
use App\Data\Requests\StockMovementLineRequestData;
use App\Data\Responses\StockMovementResultData;
use App\Enums\PimRole;
use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdjustProductStockAction
{
    use WritesAuditLog;

    public function __construct(private RecordStockMovementAction $recordStockMovementAction) {}

    public function handle(Request $request, User $admin, Product $product, StockMovementReason $reason, int $quantity): StockMovementResultData
    {
        // Re-checked here, never trusted from hidden UI alone (D97's pattern).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        $reference = sprintf('admin:%s:%s', $reason->value, (string) Str::ulid());

        $result = $this->recordStockMovementAction->handle(new StockMovementData(
            reference: $reference,
            lines: [new StockMovementLineRequestData(sku: $product->sku, quantity: $quantity)],
            reason: $reason,
        ));

        $this->recordAuditLog($request, $admin, 'product.stock_adjusted', $product);

        return $result;
    }
}
