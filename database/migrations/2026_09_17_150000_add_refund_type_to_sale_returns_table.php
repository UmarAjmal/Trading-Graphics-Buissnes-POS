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
        if (Schema::hasTable('sale_returns') && !Schema::hasColumn('sale_returns', 'refund_type')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->enum('refund_type', ['cash', 'credit', 'bank'])->default('credit')->after('grand_total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sale_returns') && Schema::hasColumn('sale_returns', 'refund_type')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->dropColumn('refund_type');
            });
        }
    }
};
