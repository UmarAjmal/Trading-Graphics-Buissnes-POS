<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Exports\SalesExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SaleController extends Controller
{
    public function index()
    {
        return Inertia::render('Sales/Index');
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'user', 'saleItems.product']);
        return Inertia::render('Sales/Show', [
            'sale' => $sale
        ]);
    }

    public function tableData(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'saleItems.product'])
            ->where('invoice_no', 'not like', 'OPB-%')
            ->orderBy('created_at', 'desc');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(15);

        return response()->json([
            'data' => $sales->items(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ]
        ]);
    }

    /**
     * Export sales to Excel
     */
    public function export()
    {
        return Excel::download(new SalesExport, 'sales.xlsx');
    }
}
