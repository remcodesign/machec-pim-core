<?php

namespace App\Data\Requests;

use App\Enums\StockMovementReason;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StockMovementData extends Data
{
    /**
     * @param  array<int, StockMovementLineRequestData>  $lines
     */
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $reference,
        #[Required, ArrayType]
        public array $lines,
        public StockMovementReason $reason = StockMovementReason::ApiOrder,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku' => ['required', 'string', 'max:255', 'distinct'],
            'lines.*.quantity' => ['required', 'integer'],
        ];
    }
}
