<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\PendingPayment;
use Illuminate\Database\Seeder;

class TestCreditPaymentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create a test customer for credit payment testing
        $customer = Customer::create([
            'name' => 'Test Customer Credit',
            'email' => 'testcredit@example.com',
            'phone' => '03001234567',
            'address' => 'Test Address for Credit'
        ]);

        // Get a random product for the sale
        $product = Product::first();
        
        if (!$product) {
            // Create a simple product if none exists
            $product = Product::create([
                'name' => 'Test Product for Credit',
                'barcode' => 'TEST-CREDIT-001',
                'selling_price' => 500.00,
                'cost_price' => 300.00,
                'stock_quantity' => 100,
                'category_id' => 1, // Assuming category exists
                'unit_id' => 1, // Assuming unit exists
            ]);
        }

        // Create a credit sale
        $sale = Sale::create([
            'invoice_no' => 'INV-CREDIT-' . time(),
            'customer_id' => $customer->id,
            'sold_at' => now(),
            'payment_type' => 'credit',
            'subtotal' => 1500.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'utilities_charges' => 0.00,
            'other_charges' => 0.00,
            'bill_total' => 1500.00,
            'previous_balance' => 0.00,
            'grand_total' => 1500.00,
            'paid_amount' => 0.00,
            'current_balance' => -1500.00, // Negative indicates credit owed
            'advance_used' => 0.00,
            'user_id' => 1, // Assuming admin user exists
        ]);

        // Create sale items
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => 3,
            'rate' => 500.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'units_sqft' => 0.00,
            'line_total' => 1500.00,
        ]);

        // Create pending payment record
        PendingPayment::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'amount_due' => 1500.00,
            'due_date' => now()->addDays(30),
            'settled' => false,
        ]);

        $this->command->info("Created test customer '{$customer->name}' with PKR 1500 credit sale (Sale ID: {$sale->id})");
        $this->command->info("Customer ID: {$customer->id}");
        $this->command->info("Now you can test the Add Payment functionality on the customer account page.");
    }
}