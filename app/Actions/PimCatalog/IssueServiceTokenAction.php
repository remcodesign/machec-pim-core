<?php

namespace App\Actions\PimCatalog;

use App\Concerns\WritesAuditLog;
use App\Enums\PimRole;
use App\Enums\ServiceAbility;
use App\Models\ServiceClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IssueServiceTokenAction
{
    use WritesAuditLog;

    /**
     * Mint a token for a possibly new service client. The plaintext exists
     * only long enough to display or print it once.
     *
     * @return array{client: ServiceClient, plaintextToken: string}
     */
    public function handle(Request $request, ?User $admin, string $label, ServiceAbility $ability): array
    {
        if ($admin instanceof User) {
            abort_unless($admin->hasRole(PimRole::PimAdmin->value), 403);
        }

        $client = ServiceClient::firstOrCreate(['label' => $label]);
        $newToken = $client->createToken($label, [$ability->value]);
        $plaintextToken = $newToken->plainTextToken;

        $newToken->accessToken->token_prefix = Str::of($plaintextToken)->after('|')->substr(0, 8)->value();
        $newToken->accessToken->save();

        if ($admin instanceof User) {
            $this->recordAuditLog($request, $admin, 'service_client.token_issued', $newToken->accessToken);
        }

        return ['client' => $client, 'plaintextToken' => $plaintextToken];
    }
}
