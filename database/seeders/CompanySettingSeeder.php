<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\CompanySetting::updateOrCreate([], [
            'company_name' => 'View Media Zone',
            'tagline' => 'We Deal All Kind of Media & Inks',
            'phone_1' => '062-2720822',
            'phone_2' => '0301-8647887',
            'whatsapp_number' => '03067288442',
            'email' => 'info@viewmedia.pk',
            'address' => 'Bindra Pully Stop, Multan Road, Bahawalpur',
            'website' => 'https://viewmedia.pk',
            'ntn' => '1234567-8',
            'sales_tax_no' => '1234567890123',
            'currency' => 'PKR',
            'invoice_prefix' => 'INV-',
            'footer_note' => 'Thank you for choosing View Media Zone for all your printing and media needs.',
            'print_footer_message' => 'Thank you for your business! — View Media Zone',
        ]);
    }
}
