<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUserAction
{
    use WritesAuditLog;

    /**
     * Delete an admin-managed user account (same idea as D97).
     *
     * Rejects — no-op plus an inline error, never a raw 500 — deleting the
     * acting admin's own account, or the last remaining `pim_admin`
     * account, so a `pim_admin` can never lock the back office out.
     * Adapted from customer-identity's "never delete any CustomerAdmin"
     * rule: this app has only one admin-capable role, so blocking every
     * admin-to-admin delete outright would make the delete action
     * unusable — the equivalent lockout protection here is "never delete
     * the last one left".
     */
    public function handle(Request $request, User $admin, User $target): void
    {
        // Re-checked here, never trusted from hidden UI alone (D97).
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        if ($target->id === $admin->id) {
            throw ValidationException::withMessages(['delete' => 'You cannot delete your own account.']);
        }

        if ($target->hasRole(PimRole::PimAdmin->value) && User::role(PimRole::PimAdmin->value)->count() <= 1) {
            throw ValidationException::withMessages(['delete' => 'You cannot delete the last remaining pim_admin account.']);
        }

        DB::transaction(function () use ($request, $admin, $target): void {
            $this->recordAuditLog($request, $admin, 'user.deleted', $target);

            $target->delete();
        });
    }
}
