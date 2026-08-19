<?php

namespace App\Listeners\SPK;

use App\Events\SPK\StudentRiskProfileUpdated;
use App\Jobs\GenerateTeacherInsightsJob;

class GenerateInsightsListener
{
    /**
     * Handle the event.
     * When risk profile is updated, generate teacher insights
     */
    public function handle(StudentRiskProfileUpdated $event): void
    {
        // Dispatch job to generate teacher insights asynchronously
        dispatch(new GenerateTeacherInsightsJob($event->riskProfile->id))
            ->onQueue('default');
    }
}
