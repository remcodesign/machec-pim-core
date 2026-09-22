<?php

namespace App\Livewire\Forms;

use App\Enums\StockMovementReason;
use Livewire\Form;

class StockAdjustmentForm extends Form
{
    public string $mode = 'adjust';

    public ?int $amount = null;

    public string $reason = '';

    /**
     * `ApiOrder` is never offered as a choice here — it only ever appears
     * on ledger rows written by machine callers of the stock-movements API.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'mode' => ['required', 'string', 'in:adjust,set'],
            'amount' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:'.implode(',', array_map(
                fn (StockMovementReason $reason): string => $reason->value,
                array_filter(StockMovementReason::cases(), fn (StockMovementReason $reason): bool => $reason !== StockMovementReason::ApiOrder),
            ))],
        ];
    }

    public function resolveQuantity(int $currentStock): int
    {
        $amount = (int) $this->amount;

        return $this->mode === 'set' ? $amount - $currentStock : $amount;
    }
}
