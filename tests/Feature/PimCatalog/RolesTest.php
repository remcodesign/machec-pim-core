<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('attaches a role to a user via the pivot table', function (): void {
    Role::findOrCreate('pim_admin');
    $user = User::factory()->create();

    $user->assignRole('pim_admin');

    expect($user->hasRole('pim_admin'))->toBeTrue()
        ->and(DB::table('model_has_roles')->where('model_id', $user->id)->exists())->toBeTrue();
});

test('logs in as the seeded pim_admin account and reaches a role-gated Livewire screen', function (): void {
    $this->seed(DatabaseSeeder::class);
    $admin = User::role('pim_admin')->firstOrFail();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
});

test('an account with no role cannot reach a pim_admin-gated screen', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertForbidden();
});
