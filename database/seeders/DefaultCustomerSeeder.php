<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class DefaultCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Walk-in Customer already exists
        $customer = Customer::where('name', 'Walk-in Customer')->first();

        if (!$customer) {
            Customer::create([
                'name' => 'Walk-in Customer',
                'phone' => '0000-0000000',
                'address' => 'N/A',
                'opening_balance' => 0,
                'credit_limit' => 0, // Usually walk-in customers don't have credit
            ]);
        }
    }
}
