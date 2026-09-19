<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'pim_stock_ledger')]
#[Fillable(['reference', 'sku', 'quantity', 'resulting_stock'])]
class StockLedgerEntry extends Model
{
    /**
     * This table has no `updated_at` column — one row per immutable
     * movement (D32/D34), only `created_at` is ever written.
     */
    public const UPDATED_AT = null;
}
