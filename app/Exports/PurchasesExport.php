<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchasesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Purchase::with(['supplier', 'items.product'])->latest()->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Purchase ID',
            'Supplier Name',
            'Date',
            'Status',
            'Subtotal',
            'Tax Amount',
            'Total Amount',
            'Items Count',
            'Created At',
        ];
    }

    /**
     * @param mixed $purchase
     * @return array
     */
    public function map($purchase): array
    {
        return [
            $purchase->id,
            $purchase->supplier ? $purchase->supplier->name : 'N/A',
            $purchase->purchase_date,
            $purchase->status,
            $purchase->subtotal,
            $purchase->tax_amount,
            $purchase->total_amount,
            $purchase->items->count(),
            $purchase->created_at->format('Y-m-d H:i:s'),
        ];
    }
}