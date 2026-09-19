<?php

namespace App\Console\Commands;

use App\Actions\PimCatalog\IssueServiceTokenAction;
use App\Enums\ServiceAbility;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Mint a Sanctum service token and print it once')]
#[Signature('machec:issue-service-token {label} {ability}')]
class IssueServiceTokenCommand extends Command
{
    public function handle(IssueServiceTokenAction $action): int
    {
        $ability = ServiceAbility::tryFrom((string) $this->argument('ability'));

        if (! $ability instanceof ServiceAbility) {
            $this->error(sprintf(
                'Unknown ability "%s". Expected one of: %s',
                $this->argument('ability'),
                implode(', ', array_map(fn (ServiceAbility $case): string => $case->value, ServiceAbility::cases())),
            ));

            return self::FAILURE;
        }

        $result = $action->handle(request(), null, (string) $this->argument('label'), $ability);

        $this->info('Token minted - copy it now, it will never be shown again:');
        $this->line($result['plaintextToken']);

        return self::SUCCESS;
    }
}
