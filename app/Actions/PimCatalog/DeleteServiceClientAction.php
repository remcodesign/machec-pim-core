<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Models\ServiceClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteServiceClientAction
{
    use WritesAuditLog;

    public function handle(Request $request, User $admin, ServiceClient $client): void
    {
        abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);

        DB::transaction(function () use ($request, $admin, $client): void {
            $this->recordAuditLog($request, $admin, 'service_client.deleted', $client);

            $client->tokens()->delete();
            $client->delete();
        });
    }
}
