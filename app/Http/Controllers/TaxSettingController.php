<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TaxSettingController extends Controller
{
    /**
     * Display tax configuration page.
     */
    public function index()
    {
        $settings = $this->getTaxSettings();
        $rates = $this->getTaxRates();

        return Inertia::render('TaxSettings/Index', [
            'settings' => $settings,
            'rates' => $rates
        ]);
    }

    /**
     * Update tax settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'default_rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:inclusive,exclusive',
            'display_type' => 'required|in:separate,inclusive,both',
            'tax_number' => 'nullable|string|max:255'
        ]);

        // Store in cache (in production, store in database)
        Cache::put('tax_settings', $validated, now()->addYears(1));

        return redirect()->back()->with('message', 'Tax settings updated successfully!');
    }

    /**
     * Update tax rates.
     */
    public function updateRates(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.name' => 'required|string|max:255',
            'rates.*.rate' => 'required|numeric|min:0|max:100'
        ]);

        // Store in cache (in production, store in database)
        Cache::put('tax_rates', $validated['rates'], now()->addYears(1));

        return redirect()->back()->with('message', 'Tax rates updated successfully!');
    }

    /**
     * Get tax settings.
     */
    private function getTaxSettings()
    {
        return Cache::get('tax_settings', [
            'enabled' => true,
            'default_rate' => 18.00,
            'type' => 'exclusive',
            'display_type' => 'separate',
            'tax_number' => ''
        ]);
    }

    /**
     * Get tax rates.
     */
    private function getTaxRates()
    {
        return Cache::get('tax_rates', [
            ['name' => 'GST', 'rate' => 18.00],
            ['name' => 'Service Tax', 'rate' => 15.00]
        ]);
    }
}