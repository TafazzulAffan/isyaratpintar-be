<?php

namespace App\Jobs;

use App\Events\SPK\SubjectMasteryCalculated;
use App\Models\AssessmentAttempt;
use App\Models\MataPelajaran;
use App\Services\SubjectMasteryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateSubjectMasteryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $attemptId,
    ) {}

    public function handle(SubjectMasteryService $service): void
    {
        // Get the attempt
        $attempt = AssessmentAttempt::with('assessment.mataPelajaran', 'user')->find($this->attemptId);

        if (!$attempt) {
            return;
        }

        // Calculate mastery for this subject
        $mastery = $service->calculateMastery($attempt->user, $attempt->assessment->mataPelajaran);

        // 🔥 DISPATCH MASTERY CALCULATED EVENT
        event(new SubjectMasteryCalculated($mastery));
    }
}
