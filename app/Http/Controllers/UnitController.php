<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Exports\UnitsExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::withCount('products')->latest()->paginate(15);
        
        return Inertia::render('Units/Index', [
            'units' => $units
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Units/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Unit store request received', [
            'request_data' => $request->all(),
            'method' => $request->method()
        ]);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'code' => 'required|string|max:10|unique:units,code',
            'symbol' => 'nullable|string|max:10',
        ]);

        $unit = Unit::create($validated);
        
        \Log::info('Unit created successfully', [
            'unit_id' => $unit->id,
            'unit_data' => $unit->toArray()
        ]);

        return redirect()->route('units.index')
            ->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        $unit->load(['products' => function($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('Units/Show', [
            'unit' => $unit
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        return Inertia::render('Units/Edit', [
            'unit' => $unit
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
            'code' => 'required|string|max:10|unique:units,code,' . $unit->id,
            'symbol' => 'nullable|string|max:10',
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        // Check if unit has products
        if ($unit->products()->count() > 0) {
            return redirect()->route('units.index')
                ->with('error', 'Cannot delete unit with associated products.');
        }

        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    /**
     * Get units data for DataTable
     */
    public function tableData(Request $request)
    {
        $query = Unit::withCount('products');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $units = $query->paginate($perPage);

        return response()->json($units);
    }

    /**
     * Export units to Excel
     */
    public function export()
    {
        return Excel::download(new UnitsExport, 'units.xlsx');
    }
}