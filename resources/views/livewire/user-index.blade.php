<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Users') }}</flux:heading>

        @if ($this->canManage)
            <flux:button :href="route('users.create')" variant="primary" size="sm" class="cursor-pointer" wire:navigate>
                {{ __('Create user') }}
            </flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Roles') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @foreach ($user->roles as $role)
                            <flux:badge size="sm">{{ $role->name }}</flux:badge>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button
                                :href="route('users.show', $user)"
                                size="sm"
                                class="cursor-pointer"
                                wire:navigate
                            >
                                {{ __('View') }}
                            </flux:button>

                            @if ($this->canManage)
                                <x-confirm-delete-modal
                                    :name="'delete-user-'.$user->id"
                                    :heading="__('Delete this user?')"
                                    :description="__('This permanently deletes :name and cannot be undone.', ['name' => $user->name])"
                                    :action="'deleteUser('.$user->id.')'"
                                />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $this->users->links() }}
</div>
