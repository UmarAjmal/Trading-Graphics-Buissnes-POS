<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportPurchasesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $purchases;

    public function __construct($purchases)
    {
        $this->purchases = $purchases;
    }

    public function collection()
    {
        return $this->purchases;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Purchase No',
            'Supplier',
            'Total Cost',
            'Status'
        ];
    }

    public function map($purchase): array
    {
        return [
            $purchase->purchased_at->format('Y-m-d'),
            $purchase->purchase_no,
            $purchase->supplier->name ?? 'Unknown Supplier',
            $purchase->grand_total,
            $purchase->status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
