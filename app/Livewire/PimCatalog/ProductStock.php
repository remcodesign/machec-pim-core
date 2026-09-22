<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\AdjustProductStockAction;
use App\Enums\StockMovementReason;
use App\Exceptions\StockUnavailableException;
use App\Livewire\Forms\StockAdjustmentForm;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Stock')]
class ProductStock extends Component
{
    use WithPagination;

    public Product $product;

    public StockAdjustmentForm $stockForm;

    public string $reasonFilter = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function updatingReasonFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, StockLedgerEntry>
     */
    #[Computed]
    public function entries(): LengthAwarePaginator
    {
        return StockLedgerEntry::query()
            ->where('sku', $this->product->sku)
            ->when($this->reasonFilter, fn ($query, string $reason) => $query->where('reason', $reason))
            ->latest()
            ->paginate(15);
    }

    /**
     * @return list<StockMovementReason>
     */
    #[Computed]
    public function reasons(): array
    {
        return StockMovementReason::cases();
    }

    /**
     * `ApiOrder` is excluded — it only ever appears on ledger rows written
     * by machine callers of the stock-movements API, never chosen by hand.
     *
     * @return list<StockMovementReason>
     */
    #[Computed]
    public function formReasons(): array
    {
        return array_values(array_filter(
            StockMovementReason::cases(),
            fn (StockMovementReason $reason): bool => $reason !== StockMovementReason::ApiOrder,
        ));
    }

    public function recordStockMovement(AdjustProductStockAction $action): void
    {
        $validated = $this->stockForm->validate();
        $quantity = $this->stockForm->resolveQuantity($this->product->stock);

        if ($quantity === 0) {
            $this->addError('stockForm.amount', __('That would not change the stock — nothing to apply.'));

            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $action->handle(request(), $admin, $this->product, StockMovementReason::from($validated['reason']), $quantity);
        } catch (StockUnavailableException) {
            $this->addError('stockForm.amount', __(
                'Can\'t remove :amount — only :stock in stock.',
                ['amount' => abs($quantity), 'stock' => $this->product->stock],
            ));

            return;
        }

        $this->product->refresh();
        $this->stockForm->reset();
        $this->resetPage();
        unset($this->entries);
        $this->dispatch('close-modal', name: 'record-stock-movement');
    }

    public function render(): View
    {
        return view('livewire.product-stock');
    }
}
