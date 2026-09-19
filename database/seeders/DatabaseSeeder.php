<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 'pim_admin' is a plain string literal, not the shared machec-contracts
        // RoleName enum — this app has zero Composer dependency on that
        // package (D53).
        Role::findOrCreate('pim_admin');

        $pimAdmin = User::factory()->create([
            'name' => 'PIM Admin',
            'email' => 'pim@example.com',
            'password' => Hash::make(config('services.demo.admin_password')),
        ]);
        $pimAdmin->assignRole('pim_admin');

        $this->call(CatalogSeeder::class);
    }
}
