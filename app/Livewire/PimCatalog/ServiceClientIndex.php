<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\DeleteServiceClientAction;
use App\Actions\PimCatalog\RevokeServiceTokenAction;
use App\Enums\PimRole;
use App\Models\ServiceClient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Service clients')]
class ServiceClientIndex extends Component
{
    /**
     * @return Collection<int, ServiceClient>
     */
    #[Computed]
    public function clients(): Collection
    {
        return ServiceClient::with('tokens')->orderBy('label')->get();
    }

    public function revokeToken(RevokeServiceTokenAction $action, int $tokenId): void
    {
        /** @var User $admin */
        $admin = Auth::user();

        $token = PersonalAccessToken::findOrFail($tokenId);

        $action->handle(request(), $admin, $token);

        unset($this->clients);
    }

    public function deleteClient(DeleteServiceClientAction $action, int $clientId): void
    {
        /** @var User $admin */
        $admin = Auth::user();

        $client = ServiceClient::findOrFail($clientId);

        $action->handle(request(), $admin, $client);

        unset($this->clients);
    }

    public function render(): View
    {
        $viewer = Auth::user();

        abort_unless($viewer instanceof User && $viewer->hasRole(PimRole::PimAdmin->value), 403);

        return view('livewire.service-client-index');
    }
}
