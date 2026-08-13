<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User if doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@pos.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create Sales User if doesn't exist
        User::firstOrCreate(
            ['email' => 'sales@pos.com'],
            [
                'name' => 'Sales Staff',
                'email' => 'sales@pos.com',
                'password' => Hash::make('sales123'),
                'role' => 'sales',
                'email_verified_at' => now(),
            ]
        );

        // Create Accountant User if doesn't exist
        User::firstOrCreate(
            ['email' => 'accountant@pos.com'],
            [
                'name' => 'Accountant User',
                'email' => 'accountant@pos.com',
                'password' => Hash::make('account123'),
                'role' => 'accountant',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Default users created successfully!');
        $this->command->info('   Admin: admin@pos.com / admin123');
        $this->command->info('   Sales: sales@pos.com / sales123');
        $this->command->info('   Accountant: accountant@pos.com / account123');
    }
}
