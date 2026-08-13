<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clear-expired {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired sessions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('session.driver') !== 'database') {
            $this->error('This command only works with database session driver.');
            return 1;
        }

        try {
            // Calculate expiration time based on session lifetime
            $sessionLifetime = config('session.lifetime', 60); // minutes
            $expirationTime = Carbon::now()->subMinutes($sessionLifetime)->timestamp;

            // Count expired sessions
            $expiredCount = DB::table('sessions')
                ->where('last_activity', '<', $expirationTime)
                ->count();

            if ($expiredCount === 0) {
                $this->info('No expired sessions found.');
                return 0;
            }

            $this->info("Found {$expiredCount} expired sessions.");

            // Ask for confirmation unless --force is used
            if (!$this->option('force') && !$this->confirm('Do you want to delete these expired sessions?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            // Delete expired sessions
            $deletedCount = DB::table('sessions')
                ->where('last_activity', '<', $expirationTime)
                ->delete();

            $this->info("Successfully deleted {$deletedCount} expired sessions.");

            // Log the cleanup
            \Log::info('Expired sessions cleared', [
                'deleted_count' => $deletedCount,
                'expiration_time' => Carbon::createFromTimestamp($expirationTime)->toDateTimeString(),
                'cleanup_time' => Carbon::now()->toDateTimeString()
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to clear expired sessions: ' . $e->getMessage());
            \Log::error('Session cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
