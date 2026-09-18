@props([
    'name',
    'heading',
    'action',
    'description' => null,
    'triggerLabel' => 'Delete',
    'confirmLabel' => 'Delete',
    'cancelLabel' => 'Cancel',
])

{{--
    pim-core's own local copy of machec-contracts' <x-machec::confirm-delete-modal>
    (D53/D98) — this app has zero Composer dependency on that shared package,
    so this component is independently maintained here, same markup/props.
--}}
<flux:modal.trigger name="{{ $name }}">
    <flux:button
        variant="danger"
        size="sm"
        class="cursor-pointer"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', '{{ $name }}')"
    >
        {{ $triggerLabel }}
    </flux:button>
</flux:modal.trigger>

<flux:modal name="{{ $name }}" :show="$errors->has($name)" focusable class="max-w-lg">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $heading }}</flux:heading>

            @if ($description)
                <flux:subheading>{{ $description }}</flux:subheading>
            @endif

            <flux:error name="{{ $name }}" />
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled" class="cursor-pointer">{{ $cancelLabel }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" class="cursor-pointer" wire:click="{{ $action }}">
                {{ $confirmLabel }}
            </flux:button>
        </div>
    </div>
</flux:modal>
