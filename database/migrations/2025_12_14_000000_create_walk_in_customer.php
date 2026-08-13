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
        // 1. Create Walk-in Customer if not exists
        $walkInId = DB::table('customers')->where('name', 'Walk-in Customer')->value('id');

        if (!$walkInId) {
            $walkInId = DB::table('customers')->insertGetId([
                'name' => 'Walk-in Customer',
                'phone' => null,
                'address' => null,
                'opening_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Update existing sales with NULL customer_id to use this ID
        DB::table('sales')
            ->whereNull('customer_id')
            ->update(['customer_id' => $walkInId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to revert this data change as it would lose the association
        // But technically we could set them back to NULL where customer_id = Walk-in ID
    }
};
