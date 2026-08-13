<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@pos.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create sales user
        User::updateOrCreate(
            ['email' => 'sales@pos.com'],
            [
                'name' => 'Sales Person',
                'email' => 'sales@pos.com', 
                'password' => Hash::make('password'),
                'role' => 'sales',
            ]
        );

        // Create accountant user
        User::updateOrCreate(
            ['email' => 'accountant@pos.com'],
            [
                'name' => 'Accountant',
                'email' => 'accountant@pos.com',
                'password' => Hash::make('password'),
                'role' => 'accountant',
            ]
        );
    }
}
