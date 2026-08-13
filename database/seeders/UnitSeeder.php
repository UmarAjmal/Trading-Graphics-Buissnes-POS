<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces'],
            ['code' => 'PKT', 'name' => 'Packets'],
            ['code' => 'LTR', 'name' => 'Liter'],
            ['code' => 'SQFT', 'name' => 'Square Feet'],
            ['code' => 'KG', 'name' => 'Kilogram'],
            ['code' => 'MTR', 'name' => 'Meter'],
            ['code' => 'BOX', 'name' => 'Box'],
            ['code' => 'SET', 'name' => 'Set'],
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
