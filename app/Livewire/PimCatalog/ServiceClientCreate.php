<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\IssueServiceTokenAction;
use App\Enums\PimRole;
use App\Enums\ServiceAbility;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Issue service token')]
class ServiceClientCreate extends Component
{
    public string $label = '';

    public string $ability = '';

    public ?string $plaintextToken = null;

    public function save(IssueServiceTokenAction $action): void
    {
        $validated = $this->validate([
            'label' => ['required', 'string', 'max:255'],
            'ability' => ['required', 'string', 'in:'.implode(',', array_map(
                fn (ServiceAbility $case): string => $case->value,
                ServiceAbility::cases(),
            ))],
        ]);

        /** @var User $admin */
        $admin = Auth::user();

        $result = $action->handle(
            request(),
            $admin,
            $validated['label'],
            ServiceAbility::from($validated['ability']),
        );

        $this->plaintextToken = $result['plaintextToken'];
    }

    public function dismissSecret(): void
    {
        $this->plaintextToken = null;

        $this->redirect(route('service-clients.index'), navigate: true);
    }

    public function render(): View
    {
        $viewer = Auth::user();

        abort_unless($viewer instanceof User && $viewer->hasRole(PimRole::PimAdmin->value), 403);

        return view('livewire.service-client-create');
    }
}
