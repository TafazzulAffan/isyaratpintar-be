<?php

namespace App\Jobs;

use App\Events\SPK\StudentRiskProfileUpdated;
use App\Models\SubjectMastery;
use App\Services\StudentRiskProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateStudentRiskProfileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $masteryId,
    ) {}

    public function handle(StudentRiskProfileService $service): void
    {
        // Get the subject mastery record
        $mastery = SubjectMastery::with('user')->find($this->masteryId);

        if (!$mastery) {
            return;
        }

        // Calculate risk profile for this student
        $profile = $service->calculateRiskProfile($mastery->user);

        // 🔥 DISPATCH RISK PROFILE UPDATED EVENT
        event(new StudentRiskProfileUpdated($profile));
    }
}
