<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\CompanySetting;
use Illuminate\Http\Response;

class PrintController extends Controller
{
    /**
     * Print A4 invoice (with copy watermark option)
     */
    public function invoiceA4(Sale $sale, Request $request): Response
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();
        $isCopy = $request->boolean('copy');

        return response()->view('prints.invoice_a4', compact('sale', 'settings', 'isCopy'))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print 80mm invoice (with copy watermark option)
     */
    public function invoice80mm(Sale $sale, Request $request): Response
    {
        $sale->load(['customer', 'user', 'saleItems.product.unit', 'saleItems.product.panaflexSpec']);
        $settings = CompanySetting::first();
        $isCopy = $request->boolean('copy');

        return response()->view('prints.invoice_80mm', compact('sale', 'settings', 'isCopy'))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print A4 return memo
     */
    public function returnA4(SaleReturn $saleReturn): Response
    {
        $saleReturn->load([
            'sale.customer', 
            'sale.user', 
            'user',
            'items.saleItem.product.unit'
        ]);
        $settings = CompanySetting::first();

        return response()->view('prints.return_a4', compact('saleReturn', 'settings'))
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print 80mm return memo
     */
    public function return80mm(SaleReturn $saleReturn): Response
    {
        $saleReturn->load([
            'sale.customer', 
            'sale.user', 
            'user',
            'items.saleItem.product.unit'
        ]);
        $settings = CompanySetting::first();

        return response()->view('prints.return_80mm', compact('saleReturn', 'settings'))
            ->header('Content-Type', 'text/html');
    }
}
