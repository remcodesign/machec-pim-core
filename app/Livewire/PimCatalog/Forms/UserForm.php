<?php

namespace App\Livewire\PimCatalog\Forms;

use App\Enums\PimRole;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    /**
     * This app's own assignable roles — currently just `pim_admin`, this
     * app's only role (D53 — a local enum, never the shared
     * machec-contracts `RoleName`).
     *
     * @return array<int, PimRole>
     */
    public static function allowedRoles(): array
    {
        return [PimRole::PimAdmin];
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->role = $user?->getRoleNames()->first() ?? '';
    }

    /**
     * Overridden instead of #[Validate] attributes: the `role` list is
     * built from allowedRoles() and `password` is only required when
     * creating.
     *
     * @return array<string, array<int, string|Password>>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:'.implode(',', array_map(
                fn (PimRole $role): string => $role->value,
                self::allowedRoles(),
            ))],
        ];

        if (! $this->user instanceof User) {
            $rules['password'] = ['required', 'string', Password::default(), 'confirmed'];
        }

        return $rules;
    }
}
