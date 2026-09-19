@props(['label', 'value'])

{{--
    pim-core's own local copy of machec-contracts' <x-machec::stat-tile>
    (D88/D53) — same markup/props, independently maintained, plus a
    top accent bar in `--admin-accent` so this app's admin visibly reads
    as a different app from the three co-located ones.
--}}
<div
    class="relative overflow-hidden rounded-xl border border-t-4 border-neutral-200 p-4 dark:border-neutral-700"
    style="border-top-color: var(--admin-accent)"
>
    <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>
    <flux:heading size="xl" class="mt-1">{{ $value }}</flux:heading>
</div>
