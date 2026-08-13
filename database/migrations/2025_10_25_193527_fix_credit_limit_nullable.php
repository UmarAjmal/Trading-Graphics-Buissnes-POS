<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First update NULL values to 0
        DB::table('customers')->whereNull('credit_limit')->update(['credit_limit' => 0]);
        
        // Then modify column to be nullable with default
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(0)->change();
        });
    }
};
