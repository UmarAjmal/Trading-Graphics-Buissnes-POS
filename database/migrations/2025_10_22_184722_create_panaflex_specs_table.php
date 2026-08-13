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
        Schema::create('panaflex_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->decimal('roll_width_inch', 8, 2);   // e.g., 126.00
            $table->decimal('roll_length_meter', 8, 2); // e.g., 50.00
            $table->decimal('rate_per_sqft', 12, 2);    // PKR per sq ft
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panaflex_specs');
    }
};
