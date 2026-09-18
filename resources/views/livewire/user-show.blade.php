<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ $user->name }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $user->email }}</flux:text>
        </div>
        <div class="flex gap-1">
            @foreach ($user->roles as $role)
                <flux:badge size="sm">{{ $role->name }}</flux:badge>
            @endforeach
        </div>
    </div>

    @if ($this->canEdit)
        <div class="relative overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="md">{{ __('Edit user') }}</flux:heading>

            <form wire:submit="updateUser" class="mt-4 max-w-lg space-y-6">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="form.name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input type="email" wire:model="form.email" :disabled="$this->editingSelf" />
                    <flux:error name="form.email" />
                </flux:field>

                <flux:field>
                    <x-role-select
                        wire:model="form.role"
                        :roles="\App\Livewire\PimCatalog\Forms\UserForm::allowedRoles()"
                        :label="__('Role')"
                    />
                    <flux:error name="form.role" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="cursor-pointer"
                    >{{ __('Save changes') }}</flux:button>
                </div>
            </form>
        </div>
    @endif
</div>
