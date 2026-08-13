<?php

namespace App\Exports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnitsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Unit::withCount('products')->latest()->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Symbol',
            'Products Count',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param mixed $unit
     * @return array
     */
    public function map($unit): array
    {
        return [
            $unit->id,
            $unit->name,
            $unit->symbol,
            $unit->products_count,
            $unit->created_at->format('Y-m-d H:i:s'),
            $unit->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}