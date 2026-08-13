<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CustomerAdvance;
use App\Models\User;

class TestAdvanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create a test customer
        $customer = Customer::firstOrCreate([
            'name' => 'Test Customer with Advance',
            'phone' => '0300-1234567'
        ], [
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'city' => 'Bahawalpur',
            'customer_type' => 'individual',
            'credit_limit' => 5000.00,
            'opening_balance' => 0.00,
        ]);

        // Find the first user (admin)
        $user = User::first();

        // Add advance payment of PKR 1000
        CustomerAdvance::create([
            'customer_id' => $customer->id,
            'date' => now(),
            'amount' => 1000.00,
            'note' => 'Test advance payment for automatic deduction testing',
            'user_id' => $user->id,
        ]);

        $this->command->info("Created test customer '{$customer->name}' with PKR 1000 advance balance.");
    }
}