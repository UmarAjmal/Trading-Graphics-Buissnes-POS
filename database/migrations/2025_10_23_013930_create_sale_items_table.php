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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable(); // for Panaflex: store user-facing L/W text
            $table->integer('quantity')->default(1);

            // pricing
            $table->decimal('rate', 12, 2);          // PKR per unit (simple) or per sq.ft (panaflex)
            $table->decimal('discount', 12, 2)->default(0); // absolute per line (optional)
            $table->decimal('tax', 12, 2)->default(0);      // absolute per line (optional)

            // units
            $table->decimal('units_sqft', 12, 2)->default(0); // base unit for invoices (sq.ft) — for simple, store qty
            $table->decimal('line_total', 12, 2);            // final amount after line-level discount+tax

            // Panaflex raw inputs (for audit/prints)
            $table->decimal('length_input', 12, 4)->nullable();
            $table->enum('length_unit', ['m','ft'])->nullable();
            $table->decimal('width_input', 12, 4)->nullable();
            $table->enum('width_unit', ['in','ft'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
