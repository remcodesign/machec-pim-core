<?php

namespace App\Data\Casts;

use App\ValueObjects\Money;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Casts a raw integer-cents value from the request into a Money VO (D22/D25).
 * Distinct from `App\Casts\MoneyCast` (the Eloquent model cast) — this one
 * implements spatie/laravel-data's own Cast contract for a Data class.
 */
class MoneyCast implements Cast
{
    /**
     * @param  CreationContext<Data>  $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Money
    {
        return new Money((int) $value);
    }
}
