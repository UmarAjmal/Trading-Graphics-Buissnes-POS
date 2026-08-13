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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // for simple items
            $table->integer('quantity')->nullable();          // if product.type = simple

            // for panaflex rolls
            $table->decimal('roll_width_inch', 8, 2)->nullable();
            $table->decimal('roll_length_meter', 8, 2)->nullable();
            $table->integer('rolls_count')->nullable();       // number of rolls bought

            $table->decimal('rate', 12, 2)->default(0);       // purchase rate per unit (qty or per roll)
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
