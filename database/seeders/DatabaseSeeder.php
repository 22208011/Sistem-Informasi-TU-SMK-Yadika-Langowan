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

        // Create admin user once, update role if user already exists
        $adminUser = User::where('email', 'admin@smk.sch.id')->first();
        if ($adminUser === null) {
            User::factory()->create([
                'name' => 'Administrator',
                'email' => 'admin@smk.sch.id',
                'role_id' => $adminRole?->id,
            ]);
        } else {
            $adminUser->update([
                'name' => 'Administrator',
                'role_id' => $adminRole?->id,
            ]);
        }

        // Create test users for each role
        $roles = Role::where('name', '!=', Role::ADMIN)->get();
        foreach ($roles as $role) {
            $email = strtolower(str_replace('_', '.', $role->name)).'@smk.sch.id';
            $existingUser = User::where('email', $email)->first();

            if ($existingUser === null) {
                User::factory()->create([
                    'name' => 'User '.$role->display_name,
                    'email' => $email,
                    'role_id' => $role->id,
                ]);
            } else {
                $existingUser->update([
                    'name' => 'User '.$role->display_name,
                    'role_id' => $role->id,
                ]);
            }
        }

        // Seed letter agenda data
        $this->call(IncomingLetterSeeder::class);
        $this->call(OutgoingLetterSeeder::class);
    }
}
