<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Service clients') }}</flux:heading>

        <flux:button
            :href="route('service-clients.create')"
            variant="primary"
            size="sm"
            class="cursor-pointer"
            wire:navigate
        >
            {{ __('Issue token') }}
        </flux:button>
    </div>

    @forelse ($this->clients as $client)
        <div class="relative overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex items-center justify-between">
                <flux:heading size="md">{{ $client->label }}</flux:heading>

                <x-confirm-delete-modal
                    :name="'delete-client-'.$client->id"
                    :heading="__('Delete this service client?')"
                    :description="__('This permanently deletes :label and revokes every token it owns.', ['label' => $client->label])"
                    :action="'deleteClient('.$client->id.')'"
                    :trigger-label="__('Delete client')"
                />
            </div>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Ability') }}</flux:table.column>
                    <flux:table.column>{{ __('Token prefix') }}</flux:table.column>
                    <flux:table.column>{{ __('Last used') }}</flux:table.column>
                    <flux:table.column>{{ __('Usage count') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($client->tokens as $token)
                        <flux:table.row :key="$token->id">
                            <flux:table.cell>{{ $token->abilities[0] ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $token->token_prefix }}&hellip;</flux:table.cell>
                            <flux:table.cell>
                                {{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                            <flux:table.cell>{{ $token->usage_count }}</flux:table.cell>
                            <flux:table.cell>
                                <x-confirm-delete-modal
                                    :name="'revoke-token-'.$token->id"
                                    :heading="__('Revoke this token?')"
                                    :description="__('Any caller presenting this token is rejected immediately.')"
                                    :action="'revokeToken('.$token->id.')'"
                                    :trigger-label="__('Revoke')"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">{{ __('No tokens issued yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @empty
        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('No service clients yet.') }}</flux:text>
    @endforelse
</div>
