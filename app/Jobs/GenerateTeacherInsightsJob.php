<?php

namespace App\Jobs;

use App\Models\StudentRiskProfile;
use App\Models\TeacherInsight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateTeacherInsightsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $riskProfileId,
    ) {}

    public function handle(): void
    {
        $profile = StudentRiskProfile::with('user')->find($this->riskProfileId);

        if (!$profile) {
            return;
        }

        // Delete old insights for this student to avoid duplicates
        TeacherInsight::forStudent($profile->user_id)->delete();

        // Generate insights based on risk level
        if ($profile->risk_level === 'critical') {
            TeacherInsight::create([
                'teacher_id' => null, // For admin dashboard
                'student_id' => $profile->user_id,
                'insight_type' => 'needs_attention',
                'message' => "{$profile->user->name} memerlukan perhatian URGENT. Risk score: {$profile->overall_risk_score}%",
                'supporting_data' => [
                    'risk_score' => $profile->overall_risk_score,
                    'risk_level' => $profile->risk_level,
                    'struggling_subjects' => $profile->struggling_subjects,
                    'attendance_risk' => $profile->attendance_risk,
                    'performance_risk' => $profile->performance_risk,
                    'engagement_risk' => $profile->engagement_risk,
                ],
            ]);
        } elseif ($profile->risk_level === 'high') {
            TeacherInsight::create([
                'teacher_id' => null,
                'student_id' => $profile->user_id,
                'insight_type' => 'at_risk',
                'message' => "{$profile->user->name} menunjukkan tanda-tanda perlu perhatian. Risk score: {$profile->overall_risk_score}%",
                'supporting_data' => [
                    'risk_score' => $profile->overall_risk_score,
                    'risk_level' => $profile->risk_level,
                    'recommendations' => $profile->recommendations,
                ],
            ]);
        }

        // Check for excellent performance
        $excellentMasteries = $profile->user->subjectMastery()
            ->with('mataPelajaran')
            ->where('status', 'excellent')
            ->get();
            
        $excellentCount = $excellentMasteries->count();

        if ($excellentCount > 0 && $profile->risk_level === 'low') {
            $subjectNames = $excellentMasteries->map(function ($mastery) {
                return $mastery->mataPelajaran ? $mastery->mataPelajaran->name : 'Mata Pelajaran';
            })->implode(', ');
            
            TeacherInsight::create([
                'teacher_id' => null,
                'student_id' => $profile->user_id,
                'insight_type' => 'excelling',
                'message' => "{$profile->user->name} menunjukkan performa excellent dalam mata pelajaran {$subjectNames}",
                'supporting_data' => [
                    'excellent_subjects_count' => $excellentCount,
                    'excellent_subjects' => $subjectNames,
                    'overall_mastery' => $profile->user->subjectMastery()->avg('mastery_percentage'),
                ],
            ]);
        }
    }
}
