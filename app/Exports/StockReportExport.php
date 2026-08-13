<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Category',
            'Type',
            'Current Stock',
            'Unit',
            'Cost Price',
            'Sale Price',
            'Stock Value (Cost)',
            'Stock Value (Sale)',
            'Sold Qty (Selected Period)',
        ];
    }

    public function map($product): array
    {
        $stockDisplay = $product->type === 'panaflex_roll' 
            ? number_format($product->stock_meters, 2) . ' m (' . number_format($product->current_stock, 2) . ' sq.ft)'
            : number_format($product->stock_quantity, 2);

        return [
            $product->name,
            $product->sku,
            $product->category->name ?? 'N/A',
            ucfirst(str_replace('_', ' ', $product->type)),
            $stockDisplay,
            $product->unit->symbol ?? '',
            number_format($product->purchase_rate, 2),
            number_format($product->sale_rate, 2),
            number_format($product->stock_value_cost, 2),
            number_format($product->stock_value_sale, 2),
            number_format($product->sold_qty_period, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
