<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('pim_admin users can visit the dashboard', function () {
    Role::findOrCreate('pim_admin');
    $user = User::factory()->create();
    $user->assignRole('pim_admin');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
