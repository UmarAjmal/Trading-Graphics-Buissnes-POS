<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * Display backup and restore page.
     */
    public function index()
    {
        $stats = $this->getDatabaseStats();
        $backupHistory = $this->getBackupHistory();
        $automatedConfig = $this->getAutomatedConfig();

        return Inertia::render('Backup/Index', [
            'stats' => $stats,
            'backupHistory' => $backupHistory,
            'automatedConfig' => $automatedConfig
        ]);
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.includeSales' => 'boolean',
            'options.includeProducts' => 'boolean',
            'options.includeCustomers' => 'boolean',
            'options.includeSettings' => 'boolean'
        ]);

        try {
            $backupData = $this->createBackupData($validated['options']);
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $path = storage_path('app/backups/' . $filename);

            // Ensure backup directory exists
            if (!File::exists(dirname($path))) {
                File::makeDirectory(dirname($path), 0755, true);
            }

            // Save backup file
            File::put($path, $backupData);

            // Store backup record in cache/database
            $this->storeBackupRecord([
                'filename' => $filename,
                'size' => File::size($path),
                'type' => 'Manual',
                'records_count' => $this->countRecords($validated['options']),
                'created_at' => now()
            ]);

            // Download the backup file
            return Response::download($path, $filename, [
                'Content-Type' => 'application/sql'
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore from backup.
     */
    public function restore(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:sql,zip,gz|max:102400', // 100MB
            'options' => 'required|array',
            'options.confirmOverwrite' => 'required|accepted'
        ]);

        try {
            $file = $request->file('file');
            $content = File::get($file->getRealPath());

            // Simple SQL execution (in production, use proper SQL parser)
            if ($file->getClientOriginalExtension() === 'sql') {
                // Execute SQL statements
                $statements = explode(';', $content);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        DB::statement($statement);
                    }
                }
            }

            return redirect()->back()->with('message', 'Database restored successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Download backup file.
     */
    public function download($id)
    {
        $backup = $this->findBackup($id);
        if (!$backup) {
            abort(404, 'Backup not found');
        }

        $path = storage_path('app/backups/' . $backup['filename']);
        if (!File::exists($path)) {
            abort(404, 'Backup file not found');
        }

        return Response::download($path, $backup['filename']);
    }

    /**
     * Delete backup file.
     */
    public function delete($id)
    {
        $backup = $this->findBackup($id);
        if (!$backup) {
            abort(404, 'Backup not found');
        }

        $path = storage_path('app/backups/' . $backup['filename']);
        if (File::exists($path)) {
            File::delete($path);
        }

        $this->removeBackupRecord($id);

        return redirect()->back()->with('message', 'Backup deleted successfully!');
    }

    /**
     * Update automated backup settings.
     */
    public function updateAutomated(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.enabled' => 'required|boolean',
            'settings.frequency' => 'required|in:daily,weekly,monthly',
            'settings.time' => 'required|date_format:H:i',
            'settings.retention_days' => 'required|integer|min:1|max:365'
        ]);

        Cache::put('automated_backup_config', $validated['settings'], now()->addYears(1));

        return redirect()->back()->with('message', 'Automated backup settings updated successfully!');
    }

    /**
     * Empty selected application data (dangerous - admin only).
     */
    public function empty(Request $request)
    {
        $validated = $request->validate([
            'confirm' => 'required|accepted'
        ]);

        // List of tables to truncate (exclude users, migrations)
        $tables = [
            'sale_items', 'sales',
            'purchase_items', 'purchases',
            'stock_moves', 'stock_batches', 'stock_adjustments',
            'products', 'panaflex_specs',
            'customers', 'suppliers',
            'returns', 'sale_returns', 'sale_return_items'
        ];

        try {
            // Disable foreign key checks for MySQL/SQLite
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } catch (\Throwable $e) {
                try {
                    DB::statement('PRAGMA foreign_keys = OFF');
                } catch (\Throwable $e) {
                    // ignore if cannot disable
                }
            }

            DB::beginTransaction();
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
            DB::commit();

            // Re-enable foreign key checks
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                try {
                    DB::statement('PRAGMA foreign_keys = ON');
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            return redirect()->back()->with('message', 'Selected tables emptied successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to empty database: ' . $e->getMessage());
        }
    }

    /**
     * Get database statistics.
     */
    private function getDatabaseStats()
    {
        try {
            return [
                'salesCount' => DB::table('sales')->count(),
                'productsCount' => DB::table('products')->count(),
                'customersCount' => DB::table('customers')->count(),
                'usersCount' => DB::table('users')->count()
            ];
        } catch (\Exception $e) {
            return [
                'salesCount' => 0,
                'productsCount' => 0,
                'customersCount' => 0,
                'usersCount' => 0
            ];
        }
    }

    /**
     * Create backup data.
     */
    private function createBackupData($options)
    {
        $sql = "-- POS System Database Backup\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";

        try {
            if ($options['includeProducts']) {
                $sql .= $this->exportTable('products');
                $sql .= $this->exportTable('categories');
                $sql .= $this->exportTable('units');
            }

            if ($options['includeCustomers']) {
                $sql .= $this->exportTable('customers');
            }

            if ($options['includeSales']) {
                $sql .= $this->exportTable('sales');
                $sql .= $this->exportTable('sale_items');
            }

            if ($options['includeSettings']) {
                $sql .= $this->exportTable('company_settings');
                $sql .= $this->exportTable('users');
            }
        } catch (\Exception $e) {
            $sql .= "-- Error creating backup: " . $e->getMessage() . "\n";
        }

        return $sql;
    }

    /**
     * Export table data as SQL.
     */
    private function exportTable($tableName)
    {
        try {
            $data = DB::table($tableName)->get();
            if ($data->isEmpty()) {
                return "-- No data in table: {$tableName}\n\n";
            }

            $sql = "-- Table: {$tableName}\n";
            $sql .= "DELETE FROM {$tableName};\n";

            foreach ($data as $row) {
                $values = [];
                foreach ((array)$row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_string($value)) {
                        $values[] = "'" . addslashes($value) . "'";
                    } else {
                        $values[] = $value;
                    }
                }
                $sql .= "INSERT INTO {$tableName} VALUES (" . implode(', ', $values) . ");\n";
            }
            
            return $sql . "\n";
        } catch (\Exception $e) {
            return "-- Error exporting table {$tableName}: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * Get backup history.
     */
    private function getBackupHistory()
    {
        return Cache::get('backup_history', []);
    }

    /**
     * Store backup record.
     */
    private function storeBackupRecord($backup)
    {
        $history = $this->getBackupHistory();
        $backup['id'] = count($history) + 1;
        $history[] = $backup;
        
        Cache::put('backup_history', $history, now()->addYears(1));
    }

    /**
     * Find backup by ID.
     */
    private function findBackup($id)
    {
        $history = $this->getBackupHistory();
        foreach ($history as $backup) {
            if ($backup['id'] == $id) {
                return $backup;
            }
        }
        return null;
    }

    /**
     * Remove backup record.
     */
    private function removeBackupRecord($id)
    {
        $history = $this->getBackupHistory();
        $history = array_filter($history, function ($backup) use ($id) {
            return $backup['id'] != $id;
        });
        
        Cache::put('backup_history', array_values($history), now()->addYears(1));
    }

    /**
     * Count records based on options.
     */
    private function countRecords($options)
    {
        $count = 0;
        try {
            if ($options['includeProducts']) {
                $count += DB::table('products')->count();
            }
            if ($options['includeCustomers']) {
                $count += DB::table('customers')->count();
            }
            if ($options['includeSales']) {
                $count += DB::table('sales')->count();
            }
            if ($options['includeSettings']) {
                $count += DB::table('users')->count();
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        return $count;
    }

    /**
     * Get automated backup configuration.
     */
    private function getAutomatedConfig()
    {
        return Cache::get('automated_backup_config', [
            'enabled' => false,
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30
        ]);
    }
}