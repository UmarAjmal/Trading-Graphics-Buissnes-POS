<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories and units
        $panaflexCategory = \App\Models\Category::where('name', 'Panaflex')->first();
        $inkCategory = \App\Models\Category::where('name', 'Inks')->first();
        $accessoryCategory = \App\Models\Category::where('name', 'Accessories')->first();
        
        $pcsUnit = \App\Models\Unit::where('code', 'PCS')->first();
        $ltrUnit = \App\Models\Unit::where('code', 'LTR')->first();

        // Create Panaflex Roll Product
        $panaflexProduct = \App\Models\Product::create([
            'name' => 'Panaflex NF 10',
            'sku' => 'PNF001',
            'category_id' => $panaflexCategory->id,
            'type' => 'panaflex_roll',
            'unit_id' => null, // Not used for panaflex rolls
            'sale_rate' => 0, // Rate is per sqft in spec
            'purchase_rate' => 12.50,
            'taxable' => true,
            'barcode' => '8901234567890',
            'active' => true,
        ]);

        // Create panaflex specification
        \App\Models\PanaflexSpec::create([
            'product_id' => $panaflexProduct->id,
            'roll_width_inch' => 126.00,
            'roll_length_meter' => 50.00,
            'rate_per_sqft' => 13.65,
        ]);

        // Create Simple Products
        \App\Models\Product::create([
            'name' => 'Solvent Ink 1L Black',
            'sku' => 'INK001',
            'category_id' => $inkCategory->id,
            'type' => 'simple',
            'unit_id' => $ltrUnit->id,
            'sale_rate' => 850.00,
            'purchase_rate' => 750.00,
            'taxable' => true,
            'barcode' => '8901234567891',
            'active' => true,
        ]);

        \App\Models\Product::create([
            'name' => 'Eyelets 100pcs',
            'sku' => 'EYE001',
            'category_id' => $accessoryCategory->id,
            'type' => 'simple',
            'unit_id' => $pcsUnit->id,
            'sale_rate' => 25.00,
            'purchase_rate' => 20.00,
            'taxable' => false,
            'barcode' => '8901234567892',
            'active' => true,
        ]);

        \App\Models\Product::create([
            'name' => 'Double Side Tape 2" x 50m',
            'sku' => 'TAPE01',
            'category_id' => $accessoryCategory->id,
            'type' => 'simple',
            'unit_id' => $pcsUnit->id,
            'sale_rate' => 180.00,
            'purchase_rate' => 150.00,
            'taxable' => true,
            'barcode' => '8901234567893',
            'active' => true,
        ]);

        // Create another panaflex roll with different specifications
        $panaflexProduct2 = \App\Models\Product::create([
            'name' => 'Panaflex Premium 440GSM',
            'sku' => 'PNF002',
            'category_id' => $panaflexCategory->id,
            'type' => 'panaflex_roll',
            'unit_id' => null,
            'sale_rate' => 0,
            'purchase_rate' => 15.00,
            'taxable' => true,
            'barcode' => '8901234567894',
            'active' => true,
        ]);

        \App\Models\PanaflexSpec::create([
            'product_id' => $panaflexProduct2->id,
            'roll_width_inch' => 60.00,
            'roll_length_meter' => 100.00,
            'rate_per_sqft' => 18.50,
        ]);
    }
}
