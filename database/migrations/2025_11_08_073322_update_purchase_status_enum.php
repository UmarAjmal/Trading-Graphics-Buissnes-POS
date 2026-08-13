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
        // Update any 'draft' or 'partial' status to 'pending'
        DB::table('purchases')
            ->whereIn('status', ['draft', 'partial'])
            ->update(['status' => 'pending']);

        // For SQLite, we need to recreate the table with new enum values
        // Since SQLite doesn't support ALTER COLUMN directly
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't have strict enum, so we just update the values
            // The validation will be handled at application level
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original enum values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('draft', 'pending', 'ordered', 'received', 'partial', 'cancelled') DEFAULT 'pending'");
        }
    }
};
