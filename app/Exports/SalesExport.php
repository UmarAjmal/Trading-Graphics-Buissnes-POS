<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

class SalesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Sale::with(['customer', 'user'])
            ->orderBy('sold_at', 'desc');

        // Apply same filters as in SalesController
        if (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $query->whereBetween('sold_at', [
                \Carbon\Carbon::parse($this->filters['date_from'])->startOfDay(),
                \Carbon\Carbon::parse($this->filters['date_to'])->endOfDay(),
            ]);
        }

        if (!empty($this->filters['payment_type']) && $this->filters['payment_type'] !== 'all') {
            $query->where('payment_type', $this->filters['payment_type']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Date',
            'Customer',
            'Payment Type',
            'Cashier',
            'Subtotal',
            'Discount',
            'Tax',
            'Other Charges',
            'Grand Total',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_no,
            $sale->sold_at->format('Y-m-d H:i'),
            $sale->customer->name ?? 'Walk-in Customer',
            ucfirst($sale->payment_type),
            $sale->user->name ?? 'Unknown',
            number_format($sale->subtotal, 2),
            number_format($sale->discount_total, 2),
            number_format($sale->tax_total, 2),
            number_format($sale->other_charges, 2),
            number_format($sale->grand_total, 2),
        ];
    }
}
