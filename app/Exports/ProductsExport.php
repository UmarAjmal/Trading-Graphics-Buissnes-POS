<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::with(['category', 'unit', 'panaflexSpec'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'type',
            'name',
            'sku',
            'category',
            'unit',
            'sale_rate',
            'purchase_rate',
            'taxable',
            'barcode',
            'roll_width_inch',
            'roll_length_meter',
            'rate_per_sqft',
            'active',
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function map($product): array
    {
        return [
            $product->type,
            $product->name,
            $product->sku,
            $product->category?->name,
            $product->unit?->code,
            $product->sale_rate,
            $product->purchase_rate,
            $product->taxable ? 'TRUE' : 'FALSE',
            $product->barcode,
            $product->panaflexSpec?->roll_width_inch,
            $product->panaflexSpec?->roll_length_meter,
            $product->panaflexSpec?->rate_per_sqft,
            $product->active ? 'TRUE' : 'FALSE',
        ];
    }
}
