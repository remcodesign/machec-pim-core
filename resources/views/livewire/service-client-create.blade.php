<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <flux:heading size="lg">{{ __('Issue service token') }}</flux:heading>

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Label') }}</flux:label>
            <flux:input wire:model="label" placeholder="{{ __('e.g. bff prod') }}" />
            <flux:error name="label" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Ability') }}</flux:label>
            <flux:select wire:model="ability">
                <flux:select.option value="">{{ __('Select an ability') }}</flux:select.option>

                @foreach (\App\Enums\ServiceAbility::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->value }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="ability" />
        </flux:field>

        <div class="flex justify-end gap-2">
            <flux:button
                :href="route('service-clients.index')"
                variant="filled"
                class="cursor-pointer"
                wire:navigate
            >{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">{{ __('Issue token') }}</flux:button>
        </div>
    </form>

    @if ($plaintextToken)
        <x-reveal-once-secret
            name="reveal-service-token"
            :secret="$plaintextToken"
            dismiss-action="dismissSecret"
            :heading="__('Copy this token now')"
            :description="__('This is the only time this token is shown. Paste it into the calling app\'s own secret store now.')"
        />
    @endif
</div>
