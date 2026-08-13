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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // common
            $table->string('batch_no')->nullable();
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();

            // simple items (qty)
            $table->integer('qty_total')->nullable();
            $table->integer('qty_remaining')->nullable();

            // panaflex (meters by roll)
            $table->decimal('roll_width_inch', 8, 2)->nullable();     // should match product spec
            $table->decimal('meters_total', 12, 2)->nullable();
            $table->decimal('meters_remaining', 12, 2)->nullable();

            $table->date('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
