<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateParentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('name', 'orang_tua')->first();
        
        if (!$role) {
            $this->command->error('Role orang_tua not found!');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'orang.tua@smk.sch.id'],
            [
                'name' => 'Orang Tua Demo',
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ]
        );

        // Link guardian records to this user
        $guardian = Guardian::first();
        if ($guardian && !$guardian->user_id) {
            $guardian->user_id = $user->id;
            $guardian->save();
            $this->command->info('Linked guardian "' . $guardian->name . '" to user');
        }

        $this->command->info('Parent user created/found: ' . $user->email);
    }
}
