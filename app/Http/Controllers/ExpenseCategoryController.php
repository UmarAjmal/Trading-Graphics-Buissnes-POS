<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::latest()->get();
        return Inertia::render('Expenses/Categories', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string'
        ]);

        ExpenseCategory::create($request->all());

        return redirect()->back()->with('success', 'Category created successfully');
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'description' => 'nullable|string'
        ]);

        $category->update($request->all());

        return redirect()->back()->with('success', 'Category updated successfully');
    }

    public function destroy(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category with associated expenses');
        }
        
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully');
    }
}
