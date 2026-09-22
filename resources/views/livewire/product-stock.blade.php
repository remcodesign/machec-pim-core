<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Stock') }}</flux:heading>
            <flux:subheading>{{ $product->name }} &middot; {{ $product->sku }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button
                :href="route('products.edit', $product)"
                variant="filled"
                size="sm"
                class="cursor-pointer"
                wire:navigate
            >
                {{ __('Edit product') }}
            </flux:button>

            <flux:button :href="route('products.index')" variant="ghost" size="sm" class="cursor-pointer" wire:navigate>
                {{ __('Back to products') }}
            </flux:button>
        </div>
    </div>

    <div class="flex items-center gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:text class="text-2xl font-semibold">{{ __('Current stock: :stock', ['stock' => $product->stock]) }}</flux:text>

        <flux:modal.trigger name="record-stock-movement">
            <flux:button
                variant="primary"
                size="sm"
                class="cursor-pointer"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'record-stock-movement')"
            >
                {{ __('Adjust stock') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:modal
        name="record-stock-movement"
        :show="$errors->hasAny(['stockForm.amount', 'stockForm.reason', 'stockForm.mode'])"
        focusable
        class="max-w-lg"
    >
        <form wire:submit="recordStockMovement" class="space-y-6">
            <flux:heading size="lg">{{ __('Adjust stock') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Reason') }}</flux:label>
                <flux:select wire:model="stockForm.reason">
                    <flux:select.option value="">{{ __('Select a reason') }}</flux:select.option>
                    @foreach ($this->formReasons as $reasonOption)
                        <flux:select.option value="{{ $reasonOption->value }}">
                            {{ $reasonOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="stockForm.reason" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Mode') }}</flux:label>
                <flux:select wire:model.live="stockForm.mode">
                    <flux:select.option value="adjust">{{ __('Adjust by amount (+/-)') }}</flux:select.option>
                    <flux:select.option value="set">{{ __('Set to counted total') }}</flux:select.option>
                </flux:select>
                <flux:error name="stockForm.mode" />
            </flux:field>

            <flux:field>
                <flux:label> {{ $stockForm->mode === 'set' ? __('Counted total') : __('Amount (+/-)') }} </flux:label>
                <flux:input type="number" wire:model="stockForm.amount" />
                <flux:description>
                    {{
                        $stockForm->mode === 'set'
                        ? __('Enter the total stock you counted — the difference with the current stock will be applied.')
                        : __('Enter a positive number to add stock, or a negative number to remove it.')
                    }}
                </flux:description>
                <flux:error name="stockForm.amount" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="cursor-pointer">{{ __('Apply') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <div class="flex flex-col gap-4">
        <div class="flex items-center gap-4">
            <flux:heading size="sm" class="shrink-0 whitespace-nowrap">{{ __('Movement history') }}</flux:heading>

            <div class="w-48 shrink-0">
                <flux:select wire:model.live="reasonFilter" size="sm">
                    <flux:select.option value="">{{ __('All reasons') }}</flux:select.option>
                    @foreach ($this->reasons as $reasonOption)
                        <flux:select.option value="{{ $reasonOption->value }}">
                            {{ $reasonOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Quantity') }}</flux:table.column>
                <flux:table.column>{{ __('Resulting stock') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->entries as $entry)
                    <flux:table.row :key="$entry->id">
                        <flux:table.cell>{{ $entry->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $entry->reason_enum->color() }}">
                                {{ $entry->reason_enum->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $entry->reference }}</flux:table.cell>
                        <flux:table.cell class="{{ $entry->quantity >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $entry->quantity >= 0 ? '+' : '' }}{{ $entry->quantity }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $entry->resulting_stock }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('No stock movements yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->entries->links() }}
    </div>
</div>
