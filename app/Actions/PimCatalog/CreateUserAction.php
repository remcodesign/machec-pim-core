<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUserAction
{
    use WritesAuditLog;

    /**
     * Create an admin-managed user account and assign it a role — same
     * idea as customer-identity's Step 2.9/D97, built independently here
     * since this app has zero Composer dependency on machec-contracts (D53).
     */
    public function handle(Request $request, User $admin, string $name, string $email, string $password, PimRole $role): User
    {
        // Re-checked here, never trusted from hidden UI alone (D97).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        Validator::make(
            ['email' => $email],
            ['email' => Rule::unique(User::class)],
        )->validate();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole($role->value);

        $this->recordAuditLog($request, $admin, 'user.created', $user);

        return $user;
    }
}
