<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Data;

class StockMovementLineRequestData extends Data
{
    public function __construct(
        public string $sku,
        public int $quantity,
    ) {}
}
