<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Seed positions
        $this->call(PositionSeeder::class);

        // Get admin role
        $adminRole = Role::where('name', Role::ADMIN)->first();

        // Create admin user
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@smk.sch.id',
            'role_id' => $adminRole->id,
        ]);

        // Create test users for each role
        $roles = Role::where('name', '!=', Role::ADMIN)->get();
        foreach ($roles as $role) {
            User::factory()->create([
                'name' => 'User ' . $role->display_name,
                'email' => strtolower(str_replace('_', '.', $role->name)) . '@smk.sch.id',
                'role_id' => $role->id,
            ]);
        }

        // Seed letter agenda data
        $this->call(IncomingLetterSeeder::class);
        $this->call(OutgoingLetterSeeder::class);
    }
}
