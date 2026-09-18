<?php

namespace App\Livewire\PimCatalog;

use App\Actions\PimCatalog\DeleteUserAction;
use App\Enums\PimRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Users')]
class UserIndex extends Component
{
    use WithPagination;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::with('roles')->orderBy('name')->paginate(15);
    }

    #[Computed]
    public function canManage(): bool
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return $viewer->hasRole(PimRole::PimAdmin->value);
    }

    public function deleteUser(DeleteUserAction $action, int $userId): void
    {
        /** @var User $admin */
        $admin = Auth::user();

        $target = User::findOrFail($userId);

        try {
            $action->handle(request(), $admin, $target);
        } catch (ValidationException $e) {
            $this->addError("delete-user-{$userId}", (string) $e->validator->errors()->first('delete'));

            return;
        }

        unset($this->users);
    }

    public function render(): View
    {
        return view('livewire.user-index');
    }
}
