<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Data;

class StockMovementLineData extends Data
{
    public function __construct(
        public string $sku,
        public int $quantity_applied,
        public int $resulting_stock,
        public int $price_cents,
        public string $name,
    ) {}
}
