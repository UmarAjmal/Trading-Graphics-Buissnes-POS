<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            // Make sale_id and customer_id nullable to support supplier payments
            $table->foreignId('sale_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->change();
            
            // Add supplier support
            $table->foreignId('supplier_id')->nullable()->after('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->after('sale_id')->constrained()->cascadeOnDelete();
            
            // Add prepayment flag and amount
            $table->boolean('is_prepayment')->default(false)->after('settled');
            $table->decimal('amount', 12, 2)->default(0)->after('is_prepayment');
            
            // Add payment method
            $table->string('payment_method')->nullable()->after('amount');
            
            // Add note/description
            $table->text('note')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'purchase_id', 'is_prepayment', 'amount', 'payment_method', 'note']);
        });
    }
};
