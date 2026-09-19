<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Categories') }}</flux:heading>

        <flux:button
            :href="route('categories.create')"
            variant="primary"
            size="sm"
            class="cursor-pointer"
            wire:navigate
        >
            {{ __('Create category') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Parent') }}</flux:table.column>
            <flux:table.column>{{ __('Filterable attributes') }}</flux:table.column>
            <flux:table.column>{{ __('Products') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->categories as $category)
                <flux:table.row :key="$category->id">
                    <flux:table.cell>{{ $category->name }}</flux:table.cell>
                    <flux:table.cell>{{ $category->slug }}</flux:table.cell>
                    <flux:table.cell>{{ $category->parent?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @foreach ($category->filterable_attributes as $attributeKey)
                            <flux:badge size="sm">{{ $attributeKey }}</flux:badge>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell>{{ $category->products_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            :href="route('categories.edit', $category)"
                            size="sm"
                            class="cursor-pointer"
                            wire:navigate
                        >
                            {{ __('Edit') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
