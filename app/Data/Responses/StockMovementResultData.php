<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Data;

class StockMovementResultData extends Data
{
    /**
     * @param  array<int, StockMovementLineData>  $lines
     */
    public function __construct(
        public bool $applied,
        public array $lines,
    ) {}
}
