<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class Sku
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('A SKU cannot be empty.');
        }
    }
}
