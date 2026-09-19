<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeServiceTokenAction
{
    use WritesAuditLog;

    public function handle(Request $request, User $admin, PersonalAccessToken $token): void
    {
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        $this->recordAuditLog($request, $admin, 'service_client.token_revoked', $token);

        $token->delete();
    }
}
