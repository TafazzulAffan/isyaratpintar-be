<?php

namespace App\Console\Commands;

use App\Jobs\GenerateTeacherInsightsJob;
use App\Models\StudentRiskProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class GenerateAllTeacherInsights extends Command
{
    protected $signature = 'spk:generate-insights';

    protected $description = 'Generate teacher insights based on student risk profiles';

    public function handle(): int
    {
        $profiles = StudentRiskProfile::all();
        $bar = $this->output->createProgressBar(count($profiles));
        $bar->start();

        foreach ($profiles as $profile) {
            dispatch(new GenerateTeacherInsightsJob($profile->id));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Teacher insights generation dispatched for {$profiles->count()} students");
        return 0;
    }
}
