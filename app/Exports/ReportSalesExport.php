<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Invoice No',
            'Customer',
            'Total',
            'Payment Method',
            'Cashier'
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->created_at->format('Y-m-d H:i'),
            $sale->invoice_no,
            $sale->customer->name ?? 'Walk-in Customer',
            $sale->bill_total,
            $sale->payment_type,
            $sale->user->name ?? 'Unknown'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
