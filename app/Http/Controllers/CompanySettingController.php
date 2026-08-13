<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanySettingController extends Controller
{
    /**
     * Display the company settings page.
     */
    public function index()
    {
        $settings = CompanySetting::current();
        
        return Inertia::render('Settings/CompanyPage', [
            'settings' => $settings->toArray(),
        ]);
    }

    /**
     * Update the company settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:4096', // max 4MB
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:255',
            'ntn' => 'nullable|string|max:20',
            'sales_tax_no' => 'nullable|string|max:20',
            'currency' => 'required|string|max:10',
            'invoice_prefix' => 'required|string|max:10',
            'footer_note' => 'nullable|string|max:500',
            'print_footer_message' => 'nullable|string|max:500',
        ]);

        $data = $validated;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $settings = CompanySetting::current();
            
            // Delete old logo if exists
            if ($settings->logo_path) {
                Storage::delete($settings->logo_path);
            }
            
            // Store new logo
            $logoPath = $request->file('logo')->store('company', 'public');
            $data['logo_path'] = $logoPath;
        }

        // Remove logo from data array as it's handled above
        unset($data['logo']);

        // Update or create settings
        CompanySetting::updateSettings($data);

        return redirect()->back()->with('success', 'Company details updated successfully!');
    }

    /**
     * API endpoint to get company settings
     */
    public function apiGet()
    {
        try {
            $settings = CompanySetting::current();
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load company settings',
            ], 500);
        }
    }

    /**
     * API endpoint to update company settings
     */
    public function apiUpdate(Request $request)
    {
        try {
            \Log::info('Company settings update request received', [
                'request_data' => $request->except(['logo']),
                'has_logo' => $request->hasFile('logo'),
                'logo_info' => $request->hasFile('logo') ? [
                    'original_name' => $request->file('logo')->getClientOriginalName(),
                    'size' => $request->file('logo')->getSize(),
                    'mime_type' => $request->file('logo')->getMimeType()
                ] : null
            ]);
            
            // Log all request data for debugging
            \Log::info('Company settings validation request', [
                'all_data' => $request->all(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('logo'),
            ]);
            
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'tagline' => 'nullable|string|max:255',
                'logo' => 'nullable|image|max:2048', // Increased to 2MB for testing
                'phone_1' => 'nullable|string|max:20',
                'phone_2' => 'nullable|string|max:20',
                'whatsapp_number' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'ntn' => 'nullable|string|max:20',
                'sales_tax_no' => 'nullable|string|max:20',
                'currency' => 'required|string|max:10',
                'invoice_prefix' => 'required|string|max:10',
                'footer_note' => 'nullable|string|max:500',
                'print_footer_message' => 'nullable|string|max:500',
            ]);

            $data = $validated;

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $settings = CompanySetting::current();
                
                // Delete old logo if exists
                if ($settings->logo_path) {
                    \Storage::disk('public')->delete($settings->logo_path);
                }
                
                // Store new logo
                $logoPath = $request->file('logo')->store('company', 'public');
                $data['logo_path'] = $logoPath;
            }

            // Remove logo from data array as it's handled above
            unset($data['logo']);

            $updatedSettings = CompanySetting::updateSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Company settings updated successfully!',
                'data' => $updatedSettings->fresh() // Fresh instance to get updated logo_url
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Company settings validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['logo']) // Don't log file data
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . collect($e->errors())->flatten()->first(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Company settings update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company settings: ' . $e->getMessage(),
            ], 500);
        }
    }
}
