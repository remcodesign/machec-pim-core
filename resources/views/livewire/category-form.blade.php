<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <flux:heading size="lg">{{ $category ? __('Edit category') : __('Create category') }}</flux:heading>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="form.name" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Slug') }}</flux:label>
            <flux:input wire:model="form.slug" />
            <flux:error name="form.slug" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Parent category') }}</flux:label>
            <flux:select wire:model="form.parent_id">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->parentOptions as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.parent_id" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Filterable attributes') }}</flux:label>
            <flux:description>
                {{ __('One storefront filter per line (D67). Editing a line renames it across this category\'s own products; deleting one removes it from them too.') }}
            </flux:description>
            <flux:error name="form.filterable_attributes" />

            <div class="space-y-2">
                @foreach ($form->filterable_attributes as $index => $value)
                    <div class="flex items-center gap-2" wire:key="attribute-line-{{ $index }}">
                        <flux:input
                            wire:model="form.filterable_attributes.{{ $index }}"
                            placeholder="{{ __('e.g. amperage') }}"
                        />

                        @if ($index < $this->persistedAttributeCount())
                            <x-confirm-delete-modal
                                :name="'delete-attribute-'.$category->filterable_attributes[$index]"
                                :heading="__('Delete this attribute?')"
                                :description="__('This removes “:key” from every product in this category and cannot be undone.', ['key' => $category->filterable_attributes[$index]])"
                                :action="'removeAttribute(\''.$category->filterable_attributes[$index].'\')'"
                                trigger-label="{{ __('Remove') }}"
                            />
                        @else
                            <flux:button
                                variant="ghost"
                                size="sm"
                                class="cursor-pointer"
                                wire:click="removeAttributeLine({{ $index }})"
                            >
                                {{ __('Remove') }}
                            </flux:button>
                        @endif
                    </div>
                @endforeach
            </div>

            <flux:button variant="filled" size="sm" class="cursor-pointer" wire:click="addAttributeLine">
                {{ __('+ Add attribute') }}
            </flux:button>
        </flux:field>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" class="cursor-pointer"> {{ __('Save') }} </flux:button>

            <flux:button :href="route('categories.index')" variant="filled" class="cursor-pointer" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
