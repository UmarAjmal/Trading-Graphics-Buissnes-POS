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
        // Using raw SQL is safer for ENUM modification in MySQL
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_type ENUM('cash', 'credit', 'bank') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting might be dangerous if there are 'bank' values, but we define it anyway
        // We cannot easily revert if we have 'bank' data, so we might just leave 'bank' or handle it 
        // For strict reversal:
        // DB::statement("ALTER TABLE sales MODIFY COLUMN payment_type ENUM('cash', 'credit') NOT NULL");
        // But practically, we usually just leave the expanded enum.
    }
};
