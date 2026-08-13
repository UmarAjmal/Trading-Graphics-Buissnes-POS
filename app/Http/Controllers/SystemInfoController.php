<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class SystemInfoController extends Controller
{
    /**
     * Display system information page.
     */
    public function index()
    {
        $systemInfo = $this->getSystemInfo();
        $healthChecks = $this->runHealthChecks();

        return Inertia::render('SystemInfo/Index', [
            'systemInfo' => $systemInfo,
            'healthChecks' => $healthChecks
        ]);
    }

    /**
     * Get comprehensive system information.
     */
    private function getSystemInfo()
    {
        return [
            'app' => $this->getAppInfo(),
            'server' => $this->getServerInfo(),
            'database' => $this->getDatabaseInfo(),
            'laravel' => $this->getLaravelInfo(),
            'performance' => $this->getPerformanceInfo()
        ];
    }

    /**
     * Get application information.
     */
    private function getAppInfo()
    {
        return [
            'name' => config('app.name', 'POS System'),
            'version' => $this->getAppVersion(),
            'environment' => App::environment(),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'status' => 'Running'
        ];
    }

    /**
     * Get server information.
     */
    private function getServerInfo()
    {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'
        ];
    }

    /**
     * Get database information.
     */
    private function getDatabaseInfo()
    {
        try {
            $connection = DB::connection();
            $config = $connection->getConfig();
            
            return [
                'type' => $config['driver'] ?? 'unknown',
                'version' => $this->getDatabaseVersion(),
                'name' => $config['database'] ?? 'unknown',
                'size' => $this->getDatabaseSize(),
                'tables' => $this->getTableCount(),
                'connected' => true
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'unknown',
                'version' => 'unknown',
                'name' => 'unknown',
                'size' => 0,
                'tables' => 0,
                'connected' => false
            ];
        }
    }

    /**
     * Get Laravel framework information.
     */
    private function getLaravelInfo()
    {
        return [
            'version' => app()->version(),
            'php_version' => PHP_VERSION,
            'composer_version' => $this->getComposerVersion(),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default')
        ];
    }

    /**
     * Get performance information.
     */
    private function getPerformanceInfo()
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'php_extensions' => count(get_loaded_extensions()),
            'disk_usage' => $this->getDiskUsage(),
            'disk_free' => disk_free_space(base_path()),
            'uptime' => $this->getServerUptime()
        ];
    }

    /**
     * Run health checks.
     */
    private function runHealthChecks()
    {
        $checks = [
            [
                'name' => 'Database Connection',
                'status' => $this->checkDatabaseConnection()
            ],
            [
                'name' => 'File Permissions',
                'status' => $this->checkFilePermissions()
            ],
            [
                'name' => 'PHP Extensions',
                'status' => $this->checkPhpExtensions()
            ],
            [
                'name' => 'Storage Directory',
                'status' => $this->checkStorageDirectory()
            ],
            [
                'name' => 'Memory Limit',
                'status' => $this->checkMemoryLimit()
            ],
            [
                'name' => 'Environment Config',
                'status' => $this->checkEnvironmentConfig()
            ]
        ];

        return $checks;
    }

    /**
     * Check database connection.
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Check file permissions.
     */
    private function checkFilePermissions()
    {
        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(storage_path('framework/cache'));
        
        if ($storageWritable && $cacheWritable) {
            return 'healthy';
        }
        
        return 'error';
    }

    /**
     * Check PHP extensions.
     */
    private function checkPhpExtensions()
    {
        $required = ['pdo', 'mbstring', 'tokenizer', 'json'];
        $missing = [];
        
        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }
        
        return empty($missing) ? 'healthy' : 'warning';
    }

    /**
     * Check storage directory.
     */
    private function checkStorageDirectory()
    {
        $directories = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs')
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir) || !is_writable($dir)) {
                return 'error';
            }
        }
        
        return 'healthy';
    }

    /**
     * Check memory limit.
     */
    private function checkMemoryLimit()
    {
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $recommended = 128 * 1024 * 1024; // 128MB
        
        if ($limit >= $recommended) {
            return 'healthy';
        } elseif ($limit >= 64 * 1024 * 1024) {
            return 'warning';
        }
        
        return 'error';
    }

    /**
     * Check environment configuration.
     */
    private function checkEnvironmentConfig()
    {
        $required = ['APP_KEY', 'APP_URL'];
        
        foreach ($required as $key) {
            if (empty(config('app.' . strtolower(str_replace('APP_', '', $key))))) {
                return 'warning';
            }
        }
        
        return 'healthy';
    }

    /**
     * Get application version.
     */
    private function getAppVersion()
    {
        if (File::exists(base_path('composer.json'))) {
            $composer = json_decode(File::get(base_path('composer.json')), true);
            return $composer['version'] ?? '1.0.0';
        }
        
        return '1.0.0';
    }

    /**
     * Get database version.
     */
    private function getDatabaseVersion()
    {
        try {
            $version = DB::select('SELECT sqlite_version() as version')[0]->version ?? 'Unknown';
            return $version;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get database size.
     */
    private function getDatabaseSize()
    {
        try {
            $dbPath = database_path('database.sqlite');
            if (File::exists($dbPath)) {
                return File::size($dbPath);
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return 0;
    }

    /**
     * Get table count.
     */
    private function getTableCount()
    {
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get Composer version.
     */
    private function getComposerVersion()
    {
        if (File::exists(base_path('composer.lock'))) {
            $lock = json_decode(File::get(base_path('composer.lock')), true);
            return $lock['_readme'][0] ?? 'Unknown';
        }
        
        return 'Unknown';
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit($limit)
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtoupper(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($unit) {
            case 'G':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'M':
                $value *= 1024 * 1024;
                break;
            case 'K':
                $value *= 1024;
                break;
        }
        
        return $value;
    }

    /**
     * Get disk usage.
     */
    private function getDiskUsage()
    {
        $total = disk_total_space(base_path());
        $free = disk_free_space(base_path());
        
        return $total - $free;
    }

    /**
     * Get server uptime.
     */
    private function getServerUptime()
    {
        if (function_exists('sys_getloadavg')) {
            return 86400; // Default to 1 day for demo
        }
        
        return 0;
    }
}