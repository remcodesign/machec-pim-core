<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <flux:heading size="lg">{{ __('Create user') }}</flux:heading>

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="form.name" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input type="email" wire:model="form.email" />
            <flux:error name="form.email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Password') }}</flux:label>
            <flux:input type="password" wire:model="form.password" viewable />
            <flux:error name="form.password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Confirm password') }}</flux:label>
            <flux:input type="password" wire:model="form.password_confirmation" viewable />
            <flux:error name="form.password_confirmation" />
        </flux:field>

        <flux:field>
            <x-role-select
                wire:model="form.role"
                :roles="\App\Livewire\PimCatalog\Forms\UserForm::allowedRoles()"
                :label="__('Role')"
            />
            <flux:error name="form.role" />
        </flux:field>

        <div class="flex justify-end gap-2">
            <flux:button
                :href="route('users.index')"
                variant="filled"
                class="cursor-pointer"
                wire:navigate
            >{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" class="cursor-pointer">{{ __('Create user') }}</flux:button>
        </div>
    </form>
</div>
