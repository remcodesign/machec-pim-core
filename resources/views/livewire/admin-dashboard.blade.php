<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <flux:heading size="lg">{{ __('Dashboard') }}</flux:heading>

    <div class="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-tile :label="__('Total products')" :value="$this->totalProducts" />
        <x-stat-tile :label="__('Total categories')" :value="$this->totalCategories" />
        <x-stat-tile :label="__('Low stock (≤5)')" :value="$this->lowStockCount" />
        <x-stat-tile :label="__('Draft products')" :value="$this->statusBreakdown['draft']" />
        <x-stat-tile :label="__('Published products')" :value="$this->statusBreakdown['published']" />
        <x-stat-tile :label="__('Archived products')" :value="$this->statusBreakdown['archived']" />
    </div>

    <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="sm" class="mb-4">{{ __('Recent stock movements') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('SKU') }}</flux:table.column>
                <flux:table.column>{{ __('Quantity') }}</flux:table.column>
                <flux:table.column>{{ __('Resulting stock') }}</flux:table.column>
                <flux:table.column>{{ __('When') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->recentStockMovements as $movement)
                    <flux:table.row :key="$movement->id">
                        <flux:table.cell>{{ $movement->reference }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->sku }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->quantity }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->resulting_stock }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->created_at?->diffForHumans() }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('No stock movements yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
