<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\PanaflexSpec;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;

class ProductsImport implements ToModel, WithHeadingRow
{
    private $createCategories;
    private $createUnits;
    private $summary = ['created' => 0, 'updated' => 0, 'errors' => 0];

    public function __construct(bool $createCategories = false, bool $createUnits = false)
    {
        $this->createCategories = $createCategories;
        $this->createUnits = $createUnits;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Handle category
            $categoryId = null;
            if (!empty($row['category'])) {
                $category = Category::where('name', $row['category'])->first();
                if (!$category && $this->createCategories) {
                    $category = Category::create(['name' => $row['category']]);
                }
                $categoryId = $category?->id;
            }

            // Handle unit
            $unitId = null;
            if (!empty($row['unit'])) {
                $unit = Unit::where('code', $row['unit'])->first();
                if (!$unit && $this->createUnits) {
                    $unit = Unit::create(['code' => $row['unit'], 'name' => $row['unit']]);
                }
                $unitId = $unit?->id;
            }

            // Prepare product data
            $productData = [
                'name' => $row['name'],
                'type' => $row['type'] ?? 'simple',
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'sale_rate' => $row['sale_rate'] ?? 0,
                'purchase_rate' => $row['purchase_rate'] ?? 0,
                'taxable' => in_array(strtoupper($row['taxable'] ?? ''), ['TRUE', '1', 'YES']),
                'barcode' => $row['barcode'] ?? null,
                'active' => !isset($row['active']) || in_array(strtoupper($row['active']), ['TRUE', '1', 'YES']),
            ];

            // Upsert by SKU
            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                $productData
            );

            // Handle panaflex spec
            if ($row['type'] === 'panaflex_roll' && 
                !empty($row['roll_width_inch']) && 
                !empty($row['roll_length_meter']) && 
                !empty($row['rate_per_sqft'])) {
                
                PanaflexSpec::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'roll_width_inch' => $row['roll_width_inch'],
                        'roll_length_meter' => $row['roll_length_meter'],
                        'rate_per_sqft' => $row['rate_per_sqft'],
                    ]
                );
            }

            if ($product->wasRecentlyCreated) {
                $this->summary['created']++;
            } else {
                $this->summary['updated']++;
            }

            return $product;

        } catch (\Exception $e) {
            $this->summary['errors']++;
            return null;
        }
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
