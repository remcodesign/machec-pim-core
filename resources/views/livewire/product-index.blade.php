<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Products') }}</flux:heading>

        <flux:button :href="route('products.create')" variant="primary" size="sm" class="cursor-pointer" wire:navigate>
            {{ __('Create product') }}
        </flux:button>
    </div>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __('Filters') }}</flux:heading>

            @if ($this->hasActiveFilters())
                <flux:button wire:click="clearFilters" variant="ghost" size="sm" class="cursor-pointer">
                    {{ __('Clear filters') }}
                </flux:button>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <flux:select wire:model.live="category" label="{{ __('Category') }}">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->categories as $categoryOption)
                    <flux:select.option value="{{ $categoryOption->slug }}">
                        {{ $categoryOption->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="brand" label="{{ __('Brand') }}">
                <flux:select.option value="">{{ __('All brands') }}</flux:select.option>
                @foreach ($this->brands as $brandOption)
                    <flux:select.option value="{{ $brandOption }}">{{ $brandOption }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" label="{{ __('Status') }}">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach ($this->statuses as $statusOption)
                    <flux:select.option value="{{ $statusOption->value }}">
                        {{ ucfirst($statusOption->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sort" label="{{ __('Sort by') }}">
                <flux:select.option value="name_asc">{{ __('Name (A–Z)') }}</flux:select.option>
                <flux:select.option value="name_desc">{{ __('Name (Z–A)') }}</flux:select.option>
                <flux:select.option value="price_asc">{{ __('Price (low–high)') }}</flux:select.option>
                <flux:select.option value="price_desc">{{ __('Price (high–low)') }}</flux:select.option>
            </flux:select>
        </div>

        @if ($this->selectedCategory)
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($this->selectedCategory->filterable_attributes as $attributeKey)
                    <div>
                        <flux:input
                            wire:model.live.debounce.400ms="attributeFilters.{{ $attributeKey }}"
                            label="{{ \Illuminate\Support\Str::headline($attributeKey) }}"
                            list="attr-filter-{{ $attributeKey }}"
                        />
                        <datalist id="attr-filter-{{ $attributeKey }}">
                            @foreach ($this->selectedCategory->distinctAttributeValues($attributeKey) as $value)
                                <option value="{{ $value }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-1">
            <div class="grid grid-cols-2 gap-4 md:w-1/2">
                <flux:input
                    type="number"
                    wire:model.live.debounce.400ms="price_min"
                    label="{{ __('Min price (€)') }}"
                />
                <flux:input
                    type="number"
                    wire:model.live.debounce.400ms="price_max"
                    label="{{ __('Max price (€)') }}"
                />
            </div>

            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                @if ($this->priceRange['min'] !== null)
                    {{
                        __('Available: €:min – €:max', [
                            'min' => number_format($this->priceRange['min'] / 100, 2),
                            'max' => number_format($this->priceRange['max'] / 100, 2),
                        ])
                    }}
                @else
                    {{ __('No matching products.') }}
                @endif
            </flux:text>
        </div>
    </div>

    <flux:text class="text-zinc-500 dark:text-zinc-400">
        {{ __('Showing :count of :total products', ['count' => $this->products->count(), 'total' => $this->products->total()]) }}
    </flux:text>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Brand') }}</flux:table.column>
            <flux:table.column>{{ __('Price') }}</flux:table.column>
            <flux:table.column>{{ __('Stock') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell>{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->sku }}</flux:table.cell>
                    <flux:table.cell>{{ $product->category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->brand }}</flux:table.cell>
                    <flux:table.cell>&euro;{{ number_format($product->price_cents / 100, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ $product->stock }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm">{{ $product->status->value }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button
                                :href="route('products.edit', $product)"
                                size="sm"
                                class="cursor-pointer"
                                wire:navigate
                            >
                                {{ __('Edit') }}
                            </flux:button>

                            <flux:button
                                :href="route('products.stock', $product)"
                                size="sm"
                                variant="filled"
                                class="cursor-pointer"
                                wire:navigate
                            >
                                {{ __('Stock') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $this->products->links() }}
</div>
