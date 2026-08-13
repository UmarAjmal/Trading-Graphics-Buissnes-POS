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
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();

            // mirror key pricing fields
            $table->integer('quantity')->default(0);          // for simple items
            $table->decimal('units_sqft', 12, 2)->default(0); // for panaflex (base unit)
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0); // negative for refund
            $table->string('note')->nullable();               // scratch/damage note etc.

            // audit of original inputs (for panaflex lines)
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
        Schema::dropIfExists('sale_return_items');
    }
};
