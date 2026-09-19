<?php

namespace App\ValueObjects;

final readonly class Money
{
    public function __construct(public int $cents) {}
}
