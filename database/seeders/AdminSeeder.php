<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin Utama (Default)
        Admin::updateOrCreate(
            ['email' => 'admin@sman1baleendah.sch.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Admin Developer (Backup)
        Admin::updateOrCreate(
            ['email' => 'developer@smkweb.com'],
            [
                'name' => 'Developer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin accounts seeded.');
        $this->command->info('Login: admin@sman1baleendah.sch.id | Pass: password123');
    }
}
