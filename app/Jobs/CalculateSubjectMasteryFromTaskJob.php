<?php

namespace App\Jobs;

use App\Events\SPK\SubjectMasteryCalculated;
use App\Models\CaseSubmission;
use App\Services\SubjectMasteryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateSubjectMasteryFromTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $submissionId,
    ) {}

    public function handle(SubjectMasteryService $service): void
    {
        // Get the submission
        $submission = CaseSubmission::with('pblCase.mataPelajaran', 'user')->find($this->submissionId);

        if (!$submission || !$submission->pblCase || !$submission->pblCase->mataPelajaran) {
            return;
        }

        // Calculate mastery for this subject
        $mastery = $service->calculateMastery($submission->user, $submission->pblCase->mataPelajaran);

        // 🔥 DISPATCH MASTERY CALCULATED EVENT
        // This will automatically trigger CalculateStudentRiskProfileJob via Listener
        event(new SubjectMasteryCalculated($mastery));
    }
}
