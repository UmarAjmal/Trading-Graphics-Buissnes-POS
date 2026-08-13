<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Builder;

class ReturnsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = SaleReturn::with(['sale.customer', 'user'])
            ->orderBy('returned_at', 'desc');

        // Apply filters
        if (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $query->whereBetween('returned_at', [
                \Carbon\Carbon::parse($this->filters['date_from'])->startOfDay(),
                \Carbon\Carbon::parse($this->filters['date_to'])->endOfDay(),
            ]);
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Return No',
            'Invoice No',
            'Date',
            'Customer',
            'Processed By',
            'Subtotal',
            'Discount',
            'Tax',
            'Other Adjustments',
            'Grand Total',
            'Reason',
        ];
    }

    public function map($saleReturn): array
    {
        return [
            $saleReturn->return_no,
            $saleReturn->sale->invoice_no,
            $saleReturn->returned_at->format('Y-m-d H:i'),
            $saleReturn->sale->customer->name ?? 'Walk-in Customer',
            $saleReturn->user->name ?? 'Unknown',
            number_format($saleReturn->subtotal, 2),
            number_format($saleReturn->discount_total, 2),
            number_format($saleReturn->tax_total, 2),
            number_format($saleReturn->other_adjustments, 2),
            number_format($saleReturn->grand_total, 2),
            $saleReturn->reason,
        ];
    }
}
