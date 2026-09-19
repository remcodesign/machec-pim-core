@props([
    'name',
    'secret',
    'dismissAction',
    'heading',
    'description' => null,
])

<flux:modal name="{{ $name }}" focusable class="max-w-lg">
    <div class="space-y-6" x-data="{ copied: false }" x-init="$dispatch('modal-show', { name: '{{ $name }}' })">
        <div>
            <flux:heading size="lg">{{ $heading }}</flux:heading>

            @if ($description)
                <flux:subheading>{{ $description }}</flux:subheading>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <flux:input readonly value="{{ $secret }}" x-ref="secret" class="font-mono" />

            <flux:button
                class="cursor-pointer"
                x-on:click="
                    navigator.clipboard.writeText($refs.secret.value);
                    copied = true;
                "
            >
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </flux:button>
        </div>

        <flux:callout variant="warning" :heading="__('This value is shown only once')">
            {{ __('It cannot be retrieved again once you close this dialog - copy it now.') }}
        </flux:callout>

        <div class="flex justify-end">
            <flux:button variant="primary" class="cursor-pointer" wire:click="{{ $dismissAction }}">
                {{ __('I\'ve copied it, close') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
