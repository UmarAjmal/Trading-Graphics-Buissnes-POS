<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use App\Exports\ReturnsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Export sales to CSV
     */
    public function salesCsv(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'payment_type', 'customer_id', 'user_id']);
        $filename = 'sales_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return Excel::download(new SalesExport($filters), $filename);
    }

    /**
     * Export sales to Excel
     */
    public function salesExcel(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'payment_type', 'customer_id', 'user_id']);
        $filename = 'sales_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new SalesExport($filters), $filename);
    }

    /**
     * Export returns to CSV
     */
    public function returnsCsv(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id']);
        $filename = 'returns_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return Excel::download(new ReturnsExport($filters), $filename);
    }

    /**
     * Export returns to Excel
     */
    public function returnsExcel(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id']);
        $filename = 'returns_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new ReturnsExport($filters), $filename);
    }
}
