<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanySetting;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\PanaflexSpec;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Company Settings
        CompanySetting::updateSettings([
            'company_name' => 'Print & Panaflex Shop',
            'tagline' => 'Quality Printing Solutions',
            'phone_1' => '+92-300-1234567',
            'phone_2' => '+92-300-7654321',
            'whatsapp_number' => '+92-300-1234567',
            'email' => 'info@printshop.com',
            'address' => '123 Main Street, Karachi, Pakistan',
            'currency' => 'PKR',
            'invoice_prefix' => 'INV-',
            'footer_note' => 'Thank you for choosing our services!',
            'print_footer_message' => 'Quality guaranteed! Visit us again.',
        ]);

        // Categories
        $categories = [
            ['name' => 'Panaflex Rolls'],
            ['name' => 'Ink & Toner'],
            ['name' => 'Paper & Stationery'],
            ['name' => 'Printing Accessories'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Units
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'],
            ['code' => 'PKG', 'name' => 'Packages', 'symbol' => 'pkg'],
            ['code' => 'BTL', 'name' => 'Bottles', 'symbol' => 'btl'],
            ['code' => 'BOX', 'name' => 'Boxes', 'symbol' => 'box'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        // Panaflex Roll Products
        $panaflexCategory = Category::where('name', 'Panaflex Rolls')->first();
        
        $panaflexProducts = [
            [
                'name' => 'Panaflex Roll 126" x 50m',
                'sku' => 'PF126-50',
                'type' => 'panaflex_roll',
                'sale_rate' => 13.65,
                'purchase_rate' => 10.00,
                'stock_meters' => 200.00,
                'min_meters' => 10.00,
                'roll_width_inch' => 126,
                'roll_length_meter' => 50,
                'rate_per_sqft' => 13.65,
            ],
            [
                'name' => 'Panaflex Roll 96" x 50m',
                'sku' => 'PF96-50',
                'type' => 'panaflex_roll',
                'sale_rate' => 12.50,
                'purchase_rate' => 9.00,
                'stock_meters' => 150.00,
                'min_meters' => 10.00,
                'roll_width_inch' => 96,
                'roll_length_meter' => 50,
                'rate_per_sqft' => 12.50,
            ],
            [
                'name' => 'Panaflex Roll 72" x 50m',
                'sku' => 'PF72-50',
                'type' => 'panaflex_roll',
                'sale_rate' => 11.00,
                'purchase_rate' => 8.00,
                'stock_meters' => 100.00,
                'min_meters' => 10.00,
                'roll_width_inch' => 72,
                'roll_length_meter' => 50,
                'rate_per_sqft' => 11.00,
            ],
        ];

        foreach ($panaflexProducts as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'sku' => $productData['sku'],
                'category_id' => $panaflexCategory->id,
                'type' => $productData['type'],
                'sale_rate' => $productData['sale_rate'],
                'purchase_rate' => $productData['purchase_rate'],
                'stock_meters' => $productData['stock_meters'],
                'min_meters' => $productData['min_meters'],
                'active' => true,
            ]);

            PanaflexSpec::create([
                'product_id' => $product->id,
                'roll_width_inch' => $productData['roll_width_inch'],
                'roll_length_meter' => $productData['roll_length_meter'],
                'rate_per_sqft' => $productData['rate_per_sqft'],
            ]);
        }

        // Simple Products
        $inkCategory = Category::where('name', 'Ink & Toner')->first();
        $paperCategory = Category::where('name', 'Paper & Stationery')->first();
        $accessoriesCategory = Category::where('name', 'Printing Accessories')->first();
        
        $pcsUnit = Unit::where('code', 'PCS')->first();
        $btlUnit = Unit::where('code', 'BTL')->first();
        $pkgUnit = Unit::where('code', 'PKG')->first();

        $simpleProducts = [
            [
                'name' => 'Black Ink Cartridge',
                'sku' => 'INK-BLK-001',
                'category_id' => $inkCategory->id,
                'unit_id' => $pcsUnit->id,
                'sale_rate' => 250.00,
                'purchase_rate' => 200.00,
                'stock_quantity' => 50,
                'min_qty' => 5,
            ],
            [
                'name' => 'Color Ink Set',
                'sku' => 'INK-COL-001',
                'category_id' => $inkCategory->id,
                'unit_id' => $pcsUnit->id,
                'sale_rate' => 450.00,
                'purchase_rate' => 350.00,
                'stock_quantity' => 25,
                'min_qty' => 3,
            ],
            [
                'name' => 'A4 Paper (500 sheets)',
                'sku' => 'PAP-A4-500',
                'category_id' => $paperCategory->id,
                'unit_id' => $pkgUnit->id,
                'sale_rate' => 150.00,
                'purchase_rate' => 120.00,
                'stock_quantity' => 100,
                'min_qty' => 10,
            ],
            [
                'name' => 'Lamination Sheets',
                'sku' => 'LAM-SHT-001',
                'category_id' => $accessoriesCategory->id,
                'unit_id' => $pcsUnit->id,
                'sale_rate' => 5.00,
                'purchase_rate' => 3.00,
                'stock_quantity' => 1000,
                'min_qty' => 50,
            ],
        ];

        foreach ($simpleProducts as $productData) {
            Product::create([
                'name' => $productData['name'],
                'sku' => $productData['sku'],
                'category_id' => $productData['category_id'],
                'type' => 'simple',
                'unit_id' => $productData['unit_id'],
                'sale_rate' => $productData['sale_rate'],
                'purchase_rate' => $productData['purchase_rate'],
                'stock_quantity' => $productData['stock_quantity'],
                'min_qty' => $productData['min_qty'],
                'active' => true,
            ]);
        }

        // Sample Customers
        $customers = [
            [
                'name' => 'Ahmed Ali',
                'phone' => '+92-300-1111111',
                'email' => 'ahmed@example.com',
                'address' => '123 Main Street, Karachi',
                'opening_balance' => 0,
            ],
            [
                'name' => 'Fatima Khan',
                'phone' => '+92-300-2222222',
                'email' => 'fatima@example.com',
                'address' => '456 Park Avenue, Lahore',
                'opening_balance' => 0,
            ],
            [
                'name' => 'Muhammad Hassan',
                'phone' => '+92-300-3333333',
                'email' => 'hassan@example.com',
                'address' => '789 Garden Road, Islamabad',
                'opening_balance' => 0,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Sample users will be created by AdminUserSeeder, so skip duplicate creation
        $this->command->info('ℹ️  Sample users handled by AdminUserSeeder');
    }
}
