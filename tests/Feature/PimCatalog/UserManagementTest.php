<?php

use App\Livewire\PimCatalog\UserCreate;
use App\Livewire\PimCatalog\UserIndex;
use App\Livewire\PimCatalog\UserShow;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate('pim_admin');
});

test('pim_admin creates a user with the pim_admin role through UserCreate', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(UserCreate::class)
        ->set('form.name', 'Jane Doe')
        ->set('form.email', 'jane@example.com')
        ->set('form.password', 'a-strong-password')
        ->set('form.password_confirmation', 'a-strong-password')
        ->set('form.role', 'pim_admin')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'jane@example.com')->sole();

    expect($user->hasRole('pim_admin'))->toBeTrue()
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'user.created')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)->exists())->toBeTrue();
});

test('pim_admin updates another user\'s name through UserShow', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $user = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(UserShow::class, ['user' => $user])
        ->set('form.name', 'Updated Name')
        ->set('form.role', 'pim_admin')
        ->call('updateUser')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'user.updated')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)->exists())->toBeTrue();
});

test('pim_admin deletes another pim_admin user through UserIndex, and it writes a user.deleted pim_audit_log row', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $user = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->call('deleteUser', $user->id)
        ->assertHasNoErrors();

    expect(User::find($user->id))->toBeNull()
        ->and(AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('action', 'user.deleted')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)->exists())->toBeTrue();
});

test('a user with no role cannot mount UserCreate or submit UserShow\'s edit form or UserIndex\'s delete action', function (): void {
    $plainUser = User::factory()->create();
    $target = User::factory()->create()->assignRole('pim_admin');

    $this->actingAs($plainUser)->get(route('users.create'))->assertForbidden();

    Livewire::actingAs($plainUser)
        ->test(UserShow::class, ['user' => $target])
        ->call('updateUser')
        ->assertForbidden();

    Livewire::actingAs($plainUser)
        ->test(UserIndex::class)
        ->call('deleteUser', $target->id)
        ->assertForbidden();

    expect(User::find($target->id))->not->toBeNull();
});

test('pim_admin cannot delete their own account', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(UserIndex::class)
        ->call('deleteUser', $admin->id)
        ->assertHasErrors(["delete-user-{$admin->id}"]);

    expect(User::find($admin->id))->not->toBeNull();
});

test('pim_admin cannot delete the last remaining pim_admin account', function (): void {
    $admin = User::factory()->create()->assignRole('pim_admin');
    $otherViewer = User::factory()->create()->assignRole('pim_admin');

    Livewire::actingAs($otherViewer)
        ->test(UserIndex::class)
        ->call('deleteUser', $admin->id)
        ->assertHasNoErrors();

    // Now only one pim_admin remains ($otherViewer) — deleting them too must be rejected.
    Livewire::actingAs($otherViewer)
        ->test(UserIndex::class)
        ->call('deleteUser', $otherViewer->id)
        ->assertHasErrors(["delete-user-{$otherViewer->id}"]);

    expect(User::find($otherViewer->id))->not->toBeNull();
});

test('pim_admin editing their own account cannot change their own email, even with a manipulated request', function (): void {
    $admin = User::factory()->create(['email' => 'admin@example.com'])->assignRole('pim_admin');

    Livewire::actingAs($admin)
        ->test(UserShow::class, ['user' => $admin])
        ->set('form.name', 'Still Admin')
        ->set('form.email', 'changed@example.com')
        ->set('form.role', 'pim_admin')
        ->call('updateUser')
        ->assertHasNoErrors();

    $admin->refresh();

    expect($admin->email)->toBe('admin@example.com')
        ->and($admin->name)->toBe('Still Admin');
});
