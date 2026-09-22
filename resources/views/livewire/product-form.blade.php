<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <flux:heading size="lg">{{ $product ? __('Edit product') : __('Create product') }}</flux:heading>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:field>
            <flux:label>{{ __('SKU') }}</flux:label>
            <flux:input wire:model="form.sku" />
            <flux:error name="form.sku" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="form.name" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Brand') }}</flux:label>
            <flux:input wire:model="form.brand" list="brands" />
            <datalist id="brands">
                @foreach ($this->brands as $brandOption)
                    <option value="{{ $brandOption }}"></option>
                @endforeach
            </datalist>
            <flux:description>{{ __('Pick an existing brand or type a new one.') }}</flux:description>
            <flux:error name="form.brand" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Price (€)') }}</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.price" />
            <flux:error name="form.price" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Category') }}</flux:label>
            <flux:select wire:model.live="form.category_slug">
                <flux:select.option value="">{{ __('Select a category') }}</flux:select.option>
                @foreach ($this->categories as $categoryOption)
                    <flux:select.option value="{{ $categoryOption->slug }}">
                        {{ $categoryOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.category_slug" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model="form.status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.status" />
        </flux:field>

        @if ($this->filterableAttributeKeys !== [])
            <div class="space-y-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="sm">{{ __('Category specs') }}</flux:heading>

                @foreach ($this->filterableAttributeKeys as $attributeKey)
                    <flux:field>
                        <flux:label>{{ \Illuminate\Support\Str::headline($attributeKey) }}</flux:label>
                        <flux:input wire:model="form.attributes.{{ $attributeKey }}" list="attr-{{ $attributeKey }}" />
                        <datalist id="attr-{{ $attributeKey }}">
                            @foreach ($this->selectedCategory->distinctAttributeValues($attributeKey) as $value)
                                <option value="{{ $value }}"></option>
                            @endforeach
                        </datalist>
                        <flux:description>{{ __('Pick an existing value or type a new one.') }}</flux:description>
                    </flux:field>
                @endforeach
            </div>
        @endif

        @if ($product)
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Current stock: :stock —', ['stock' => $product->stock]) }}
                <a
                    href="{{ route('products.stock', $product) }}"
                    wire:navigate
                    class="underline"
                >{{ __('manage stock') }}</a>
            </flux:text>
        @endif

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" class="cursor-pointer"> {{ __('Save') }} </flux:button>

            <flux:button :href="route('products.index')" variant="filled" class="cursor-pointer" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
