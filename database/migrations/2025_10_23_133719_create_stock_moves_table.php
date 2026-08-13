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
        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase','sale','return','adjustment']);
            $table->foreignId('ref_id')->nullable();          // points to purchase_id OR sale_id OR sale_return_id OR adjustment id
            $table->string('ref_table')->nullable();          // 'purchases','sales','sale_returns','adjustments'
            $table->foreignId('batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->decimal('qty_change', 12, 2)->nullable();     // positive in, negative out (simple)
            $table->decimal('meters_change', 12, 2)->nullable();  // positive in, negative out (panaflex)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_moves');
    }
};
