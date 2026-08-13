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
        Schema::table('suppliers', function (Blueprint $table) {
            // Add unique constraint to name column
            $table->unique('name', 'suppliers_name_unique');
            
            // Add unique constraint to email column (but allow nulls)
            $table->index('email'); // First add index
        });
        
        // For email unique constraint with nullable, we need to handle it separately
        // SQLite doesn't support partial unique indexes easily, so we'll handle this in validation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop unique constraints
            $table->dropUnique('suppliers_name_unique');
            $table->dropIndex(['email']);
        });
    }
};
