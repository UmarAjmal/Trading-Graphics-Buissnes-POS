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
        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_return_items', 'units_sqft')) {
                $table->decimal('units_sqft', 12, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('purchase_return_items', 'length_input')) {
                $table->decimal('length_input', 12, 4)->nullable()->after('rolls_count');
            }
            if (!Schema::hasColumn('purchase_return_items', 'length_unit')) {
                $table->enum('length_unit', ['m', 'ft'])->default('m')->after('length_input');
            }
            if (!Schema::hasColumn('purchase_return_items', 'width_input')) {
                $table->decimal('width_input', 12, 4)->nullable()->after('length_unit');
            }
            if (!Schema::hasColumn('purchase_return_items', 'width_unit')) {
                $table->enum('width_unit', ['in', 'ft'])->default('in')->after('width_input');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn([
                'units_sqft',
                'length_input',
                'length_unit',
                'width_input',
                'width_unit',
            ]);
        });
    }
};
