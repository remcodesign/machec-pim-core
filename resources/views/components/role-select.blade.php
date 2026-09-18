@props([
    'roles',
    'label' => 'Role',
])

{{--
    pim-core's own local copy of machec-contracts' <x-machec::role-select>
    (D53/D98) — bound to this app's own local `App\Enums\PimRole` instead
    of the shared `RoleName` enum, same markup/props otherwise.
--}}
<flux:select {{ $attributes }} :label="$label">
    <flux:select.option value="">{{ __('Select a role') }}</flux:select.option>

    @foreach ($roles as $role)
        <flux:select.option value="{{ $role->value }}">{{ $role->value }}</flux:select.option>
    @endforeach
</flux:select>
