<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuppliersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Supplier::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name',
            'phone',
            'whatsapp',
            'email',
            'address',
            'contact_person',
            'created_at',
        ];
    }

    /**
     * @param Supplier $supplier
     * @return array
     */
    public function map($supplier): array
    {
        return [
            $supplier->name,
            $supplier->phone,
            $supplier->whatsapp,
            $supplier->email,
            $supplier->address,
            $supplier->contact_person ?? '',
            $supplier->created_at->format('Y-m-d H:i:s'),
        ];
    }
}