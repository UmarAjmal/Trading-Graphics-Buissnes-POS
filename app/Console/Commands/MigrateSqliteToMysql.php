<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToMysql extends Command
{
    protected $signature = 'db:sqlite-to-mysql';
    protected $description = 'Migrate data from SQLite to MySQL';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('SQLite to MySQL Data Migration');
        $this->info('==============================================');
        $this->newLine();

        // Tables in correct order
        $tables = [
            'users',
            'company_settings',
            'categories',
            'units',
            'suppliers',
            'customers',
            'products',
            'panaflex_specs',
            'permissions',
            'roles',
            'register_sessions',
            'sales',
            'sale_items',
            'sale_returns',
            'sale_return_items',
            'purchases',
            'purchase_items',
            'stock_batches',
            'stock_moves',
            'stock_adjustments',
            'pending_payments',
            'customer_advances',
            'customer_credit_payments',
        ];

        try {
            // Setup SQLite connection
            config(['database.connections.sqlite_source' => [
                'driver' => 'sqlite',
                'database' => database_path('database.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);

            $sqlite = DB::connection('sqlite_source');
            $mysql = DB::connection('mysql');

            $this->info('📊 Testing connections...');
            $sqlite->getPdo();
            $mysql->getPdo();
            $this->info('✅ Connections successful');
            $this->newLine();

            // Disable foreign key checks
            $mysql->statement('SET FOREIGN_KEY_CHECKS=0;');

            $totalRecords = 0;
            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            foreach ($tables as $table) {
                $this->newLine();
                $this->info("📦 Processing: {$table}");

                try {
                    // Check if table exists
                    $count = $sqlite->table($table)->count();

                    if ($count === 0) {
                        $this->warn("   📭 No data");
                        $bar->advance();
                        continue;
                    }

                    // Get all data
                    $data = $sqlite->table($table)->get()->toArray();
                    $dataArray = json_decode(json_encode($data), true);

                    // Clear MySQL table
                    $mysql->table($table)->truncate();

                    // Insert in chunks
                    $chunks = array_chunk($dataArray, 100);
                    foreach ($chunks as $chunk) {
                        $mysql->table($table)->insert($chunk);
                    }

                    $totalRecords += $count;
                    $this->info("   ✅ Migrated {$count} records");

                } catch (\Exception $e) {
                    $this->error("   ⚠️  Error: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Re-enable foreign key checks
            $mysql->statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('==============================================');
            $this->info("✅ Migration completed!");
            $this->info("📊 Total records: {$totalRecords}");
            $this->info('==============================================');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile());
            $this->error('Line: ' . $e->getLine());
            return 1;
        }
    }
}
