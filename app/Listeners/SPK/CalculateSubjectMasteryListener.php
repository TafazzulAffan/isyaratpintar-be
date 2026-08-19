<?php

namespace App\Listeners\SPK;

use App\Events\Assessment\AttemptCompleted;
use App\Jobs\CalculateSubjectMasteryJob;
use Illuminate\Support\Facades\Queue;

class CalculateSubjectMasteryListener
{
    /**
     * Handle the event.
     * When an attempt is completed, calculate subject mastery for that subject
     */
    public function handle(AttemptCompleted $event): void
    {
        // Dispatch job to calculate subject mastery asynchronously
        dispatch(new CalculateSubjectMasteryJob($event->attempt->id))
            ->onQueue('default');
    }
}
