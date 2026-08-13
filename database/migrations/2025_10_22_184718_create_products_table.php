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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['simple', 'panaflex_roll']);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete(); // for simple items (e.g., PCS)
            $table->decimal('sale_rate', 12, 2)->default(0);      // default sell rate (PKR)
            $table->decimal('purchase_rate', 12, 2)->default(0);  // last purchase/base
            $table->boolean('taxable')->default(false);
            $table->string('barcode')->nullable(); // auto if not provided
            $table->string('image_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
