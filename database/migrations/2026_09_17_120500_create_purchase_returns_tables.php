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
        if (!Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('return_no')->unique(); // e.g. PRTN-0000001
                $table->dateTime('returned_at');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('other_adjustments', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->enum('refund_type', ['cash', 'credit', 'bank'])->default('credit');
                $table->string('reason', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2)->default(0);
                $table->decimal('roll_width_inch', 12, 2)->nullable();
                $table->decimal('roll_length_meter', 12, 2)->nullable();
                $table->decimal('rolls_count', 12, 2)->default(0);
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sale_returns') && !Schema::hasColumn('sale_returns', 'refund_type')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->enum('refund_type', ['cash', 'credit', 'bank'])->default('credit')->after('grand_total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        if (Schema::hasTable('sale_returns') && Schema::hasColumn('sale_returns', 'refund_type')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->dropColumn('refund_type');
            });
        }
    }
};
