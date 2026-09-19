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

    <div class="relative overflow-hidden rounded-xl border border-neutral-300 bg-neutral-50 p-4 shadow-sm dark:border-zinc-600 dark:bg-zinc-900">
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

    <div class="relative overflow-hidden rounded-xl border border-neutral-300 bg-neutral-50 p-4 shadow-sm dark:border-zinc-600 dark:bg-zinc-900">
        <flux:heading size="sm" class="mb-4">{{ __('Recent activity') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Action') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('When') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->recentAuditLog as $entry)
                    <flux:table.row :key="$entry->id">
                        <flux:table.cell>{{ $entry->action }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->user?->name ?? __('System') }}</flux:table.cell>
                        <flux:table.cell>
                            {{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $entry->created_at?->diffForHumans() }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">{{ __('No activity yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
