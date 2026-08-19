<?php

namespace App\Console\Commands;

use App\Models\StudentActivityLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldActivityLogs extends Command
{
    protected $signature = 'activity:cleanup-old-logs {--days=90 : Days to keep (default 90)}';

    protected $description = 'Clean up old activity logs to maintain database performance';

    public function handle(): int
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $deletedCount = StudentActivityLog::where('logged_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deletedCount} activity logs older than {$days} days");
        return 0;
    }
}
