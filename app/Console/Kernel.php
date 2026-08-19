<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ===== SPK CALCULATIONS =====
        // Daily at 2:00 AM - Calculate subject mastery for all students
        $schedule->command('spk:calculate-mastery')
            ->daily()
            ->at('02:00')
            ->name('spk.mastery.calculate')
            ->withoutOverlapping();

        // Daily at 2:15 AM - Calculate risk profiles for all students
        $schedule->command('spk:calculate-risks')
            ->daily()
            ->at('02:15')
            ->name('spk.risks.calculate')
            ->withoutOverlapping();

        // Daily at 2:30 AM - Generate teacher insights
        $schedule->command('spk:generate-insights')
            ->daily()
            ->at('02:30')
            ->name('spk.insights.generate')
            ->withoutOverlapping();

        // ===== MAINTENANCE =====
        // Weekly cleanup - Sunday at 3:00 AM
        $schedule->command('activity:cleanup-old-logs --days=90')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->name('activity.cleanup.logs')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
