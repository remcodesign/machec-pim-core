<?php

namespace App\Data\Casts;

use App\ValueObjects\Sku;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class SkuCast implements Cast
{
    /**
     * @param  CreationContext<Data>  $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Sku
    {
        return new Sku((string) $value);
    }
}
