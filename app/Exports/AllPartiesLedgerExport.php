<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllPartiesLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Voucher No',
            'Party Name',
            'Description',
            'Debit',
            'Credit',
            'Balance',
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['voucher_no'],
            $row['party_name'],
            $row['description'],
            number_format($row['debit'], 2),
            number_format($row['credit'], 2),
            number_format($row['balance'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
