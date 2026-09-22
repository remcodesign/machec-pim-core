<?php

namespace Database\Factories;

use App\Enums\StockMovementReason;
use App\Models\StockLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLedgerEntry>
 */
class StockLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->uuid(),
            'sku' => fake()->unique()->bothify('sku-####'),
            'quantity' => fake()->numberBetween(-20, 20),
            'resulting_stock' => fake()->numberBetween(0, 100),
            'reason' => fake()->randomElement(array_map(
                fn (StockMovementReason $reason): string => $reason->value,
                StockMovementReason::cases(),
            )),
        ];
    }
}
