<?php

namespace App\Listeners\SPK;

use App\Events\SPK\SubjectMasteryCalculated;
use App\Jobs\CalculateStudentRiskProfileJob;

class CalculateRiskProfileListener
{
    /**
     * Handle the event.
     * When subject mastery is calculated, recalculate overall risk profile
     */
    public function handle(SubjectMasteryCalculated $event): void
    {
        // Dispatch job to calculate student risk profile asynchronously
        dispatch(new CalculateStudentRiskProfileJob($event->mastery->id))
            ->onQueue('default');
    }
}
