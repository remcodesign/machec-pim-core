<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\UpdateUserAction;
use App\Enums\PimRole;
use App\Livewire\PimCatalog\Forms\UserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User')]
class UserShow extends Component
{
    public User $user;

    public UserForm $form;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->form->setUser($user);
    }

    #[Computed]
    public function canEdit(): bool
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return $viewer->hasRole(PimRole::PimAdmin->value);
    }

    /**
     * Self-edit guardrail: an admin editing their own account can never
     * change their own email through this form.
     */
    #[Computed]
    public function editingSelf(): bool
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return $this->user->id === $viewer->id;
    }

    public function updateUser(UpdateUserAction $action): void
    {
        $validated = $this->form->validate();

        /** @var User $admin */
        $admin = Auth::user();

        $this->user = $action->handle(
            request(),
            $admin,
            $this->user,
            $validated['name'],
            $validated['email'],
            PimRole::from($validated['role']),
        );

        $this->form->setUser($this->user);
    }

    public function render(): View
    {
        return view('livewire.user-show');
    }
}
