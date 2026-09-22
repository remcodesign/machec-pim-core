<?php

namespace App\Models;

use App\Enums\StockMovementReason;
use Database\Factories\StockLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'pim_stock_ledger')]
#[Fillable(['reference', 'sku', 'quantity', 'resulting_stock', 'reason'])]
class StockLedgerEntry extends Model
{
    /** @use HasFactory<StockLedgerEntryFactory> */
    use HasFactory;

    /**
     * This table has no `updated_at` column — one row per immutable
     * movement (D32/D34), only `created_at` is ever written.
     */
    public const UPDATED_AT = null;

    /**
     * @return Attribute<StockMovementReason, never>
     */
    protected function reasonEnum(): Attribute
    {
        return Attribute::get(fn (): StockMovementReason => StockMovementReason::from($this->reason));
    }
}
