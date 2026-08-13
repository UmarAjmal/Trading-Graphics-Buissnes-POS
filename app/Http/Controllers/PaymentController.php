<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        return Inertia::render('Payments/Index', [
            'customers' => Customer::select('id', 'name', 'phone')->get(),
            'suppliers' => Supplier::select('id', 'name', 'phone')->get(),
            'payments' => Payment::with(['customer', 'supplier', 'user'])
                // ->where('payment_method', 'cash') // Commented out to allow all manual methods (Bank, Check, etc) to show
                ->whereNull('sale_id')
                ->whereNull('purchase_id')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:received,paid',
            'party_type' => 'required|in:customer,supplier',
            'party_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $id) {
            $payment = Payment::findOrFail($id);
            
            $payment->type = $validated['type'];
            $payment->amount = $validated['amount'];
            $payment->payment_date = $validated['payment_date'];
            $payment->payment_method = $validated['payment_method'];
            $payment->note = $validated['note'];
            // User ID normally stays the same or update to modifier? Keep original for now or update.
            // $payment->user_id = Auth::id(); 

            if ($validated['party_type'] === 'customer') {
                $payment->customer_id = $validated['party_id'];
                $payment->supplier_id = null;
            } else {
                $payment->supplier_id = $validated['party_id'];
                $payment->customer_id = null;
            }

            $payment->save();

            // Refresh balance snapshot
            if ($validated['party_type'] === 'customer') {
                $payment->current_balance = $payment->customer->balance;
            } else {
                $payment->current_balance = $payment->supplier->balance;
            }
            $payment->save();
        });

        return redirect()->back()->with('success', 'Payment updated successfully.');
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return redirect()->back()->with('success', 'Payment deleted successfully. Accounts reversed.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:received,paid',
            'party_type' => 'required|in:customer,supplier',
            'party_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $payment = new Payment();
            $payment->type = $validated['type'];
            $payment->amount = $validated['amount'];
            $payment->payment_date = $validated['payment_date'];
            $payment->payment_method = $validated['payment_method'];
            $payment->note = $validated['note'];
            $payment->user_id = Auth::id();

            if ($validated['party_type'] === 'customer') {
                $payment->customer_id = $validated['party_id'];
                $customer = Customer::find($validated['party_id']);
                // Calculate new balance after this payment
                // Note: getBalanceAttribute calculates live balance including this new payment if we save it first.
                // But we want to store the snapshot.
                // Let's save first, then update current_balance.
            } else {
                $payment->supplier_id = $validated['party_id'];
            }

            $payment->save();

            // Update current balance snapshot
            if ($validated['party_type'] === 'customer') {
                $payment->current_balance = $payment->customer->balance;
            } else {
                $payment->current_balance = $payment->supplier->balance;
            }
            $payment->save();
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function getPartyBalance(Request $request)
    {
        $type = $request->query('type'); // customer or supplier
        $id = $request->query('id');

        if ($type === 'customer') {
            $party = Customer::find($id);
        } else {
            $party = Supplier::find($id);
        }

        if (!$party) {
            return response()->json(['balance' => 0.00]);
        }

        // Return float to ensure frontend handles it correctly
        return response()->json(['balance' => (float) $party->balance]);
    }
}
