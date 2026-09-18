<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\CreateUserAction;
use App\Enums\PimRole;
use App\Livewire\PimCatalog\Forms\UserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create user')]
class UserCreate extends Component
{
    public UserForm $form;

    public function save(CreateUserAction $action): void
    {
        $validated = $this->form->validate();

        /** @var User $admin */
        $admin = Auth::user();

        $user = $action->handle(
            request(),
            $admin,
            $validated['name'],
            $validated['email'],
            $validated['password'],
            PimRole::from($validated['role']),
        );

        $this->redirect(route('users.show', $user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.user-create');
    }
}
