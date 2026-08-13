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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('utilities_charges', 10, 2)->default(0)->after('tax_total');
            $table->decimal('bill_total', 10, 2)->default(0)->after('other_charges');
            $table->decimal('previous_balance', 10, 2)->default(0)->after('bill_total');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('grand_total');
            $table->decimal('current_balance', 10, 2)->default(0)->after('paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'utilities_charges',
                'bill_total', 
                'previous_balance',
                'paid_amount',
                'current_balance'
            ]);
        });
    }
};
