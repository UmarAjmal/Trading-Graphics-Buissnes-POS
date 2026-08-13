<?php
/**
 * SQLite to MySQL Data Export Script
 * Run this locally to export data from SQLite
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// SQLite connection (current)
$sqlite = DB::connection('sqlite');

// MySQL connection (configure in .env first)
$mysql = DB::connection('mysql');

echo "Starting data export from SQLite to MySQL...\n\n";

$tables = [
    'users',
    'company_settings',
    'categories',
    'units',
    'products',
    'panaflex_specs',
    'customers',
    'customer_advances',
    'customer_credit_payments',
    'suppliers',
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
    'permissions',
    'roles',
    'register_sessions',
];

try {
    DB::beginTransaction();
    
    foreach ($tables as $table) {
        echo "Exporting table: {$table}...\n";
        
        // Check if table exists in SQLite
        $exists = $sqlite->select("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        
        if (empty($exists)) {
            echo "  - Table {$table} not found in SQLite, skipping...\n";
            continue;
        }
        
        // Get data from SQLite
        $data = $sqlite->table($table)->get()->toArray();
        
        if (count($data) === 0) {
            echo "  - No data in {$table}\n";
            continue;
        }
        
        // Convert to array for MySQL
        $dataArray = json_decode(json_encode($data), true);
        
        // Disable foreign key checks
        $mysql->statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate MySQL table first
        $mysql->table($table)->truncate();
        
        // Insert data in chunks
        $chunks = array_chunk($dataArray, 100);
        foreach ($chunks as $chunk) {
            $mysql->table($table)->insert($chunk);
        }
        
        // Re-enable foreign key checks
        $mysql->statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "  - Exported " . count($data) . " rows\n";
    }
    
    DB::commit();
    echo "\n✅ Export completed successfully!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
