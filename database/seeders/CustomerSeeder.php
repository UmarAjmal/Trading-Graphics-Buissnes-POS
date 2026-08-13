<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Ahmed Khan',
                'phone' => '+92-300-1234567',
                'whatsapp' => '+92-300-1234567',
                'address' => 'Shop 15, Main Market, Lahore',
                'opening_balance' => 0,
            ],
            [
                'name' => 'Fatima Enterprises',
                'phone' => '+92-321-9876543',
                'whatsapp' => '+92-321-9876543',
                'address' => 'Block B, Industrial Area, Karachi',
                'opening_balance' => 5000,
            ],
            [
                'name' => 'Muhammad Ali Printing',
                'phone' => '+92-333-5555555',
                'whatsapp' => '+92-333-5555555',
                'address' => 'Printing Street, Islamabad',
                'opening_balance' => 0,
            ],
            [
                'name' => 'Zara Advertising Agency',
                'phone' => '+92-311-2222222',
                'whatsapp' => '+92-311-2222222',
                'address' => 'Commercial Area, Faisalabad',
                'opening_balance' => 15000,
            ],
            [
                'name' => 'Hassan Graphics',
                'phone' => '+92-345-7777777',
                'whatsapp' => '+92-345-7777777',
                'address' => 'Design Plaza, Multan',
                'opening_balance' => 0,
            ],
        ];

        foreach ($customers as $customer) {
            \App\Models\Customer::create($customer);
        }
    }
}
