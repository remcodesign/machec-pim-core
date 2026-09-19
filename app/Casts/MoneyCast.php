<?php

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps a virtual `{key}` attribute onto a real `{key}_cents` integer column,
 * so `$model->price` is always a Money VO, never the raw `price_cents` int (D25).
 *
 * @implements CastsAttributes<Money, Money|int>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return new Money((int) $attributes["{$key}_cents"]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return ["{$key}_cents" => $value instanceof Money ? $value->cents : (int) $value];
    }
}
