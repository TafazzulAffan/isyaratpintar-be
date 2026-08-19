<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\CaseSubmission;
use App\Models\MataPelajaran;
use App\Models\SubjectMastery;
use App\Models\User;

class SubjectMasteryService
{
    /**
     * Calculate subject mastery for a user
     */
    public function calculateMastery(User $user, MataPelajaran $subject): SubjectMastery
    {
        // Get all completed assessments for this subject
        $assessments = AssessmentAttempt::whereHas('assessment', function ($q) use ($subject) {
            $q->where('mata_pelajaran_id', $subject->id);
        })
            ->where('user_id', $user->id)
            ->where('status', 'COMPLETED')
            ->get();

        // Get all graded tasks (PBL) for this subject
        $tasks = CaseSubmission::whereHas('pblCase', function ($q) use ($subject) {
            $q->where('mata_pelajaran_id', $subject->id);
        })
            ->where('user_id', $user->id)
            ->whereNotNull('score')
            ->get();

        if ($assessments->isEmpty() && $tasks->isEmpty()) {
            return SubjectMastery::updateOrCreate(
                ['user_id' => $user->id, 'mata_pelajaran_id' => $subject->id],
                [
                    'mastery_percentage' => 0,
                    'average_score' => 0,
                    'assessments_completed' => 0,
                    'status' => 'poor',
                    'last_assessed_at' => null,
                    'calculated_at' => now(),
                ]
            );
        }

        // Calculate average score
        $totalItems = $assessments->count() + $tasks->count();
        $totalScore = $assessments->sum('score') + $tasks->sum('score');
        $avgScore = $totalScore / $totalItems;

        // Calculate mastery percentage
        $masteryPercentage = round($avgScore, 2);

        // Determine status based on percentage
        $status = $this->determineStatus($masteryPercentage);

        // Update or create record
        $lastAssessedAt = collect([$assessments->max('completed_at'), $tasks->max('submitted_at')])->filter()->max();

        return SubjectMastery::updateOrCreate(
            ['user_id' => $user->id, 'mata_pelajaran_id' => $subject->id],
            [
                'mastery_percentage' => $masteryPercentage,
                'average_score' => round($avgScore, 2),
                'assessments_completed' => $totalItems,
                'status' => $status,
                'last_assessed_at' => $lastAssessedAt,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Calculate mastery for all subjects for a user
     */
    public function calculateAllMastery(User $user): void
    {
        $subjects = MataPelajaran::all();

        foreach ($subjects as $subject) {
            $this->calculateMastery($user, $subject);
        }
    }

    /**
     * Get all subjects user is struggling with
     */
    public function getStrugglingSubjects(User $user): array
    {
        return SubjectMastery::forUser($user->id)
            ->needsHelp()
            ->with('mataPelajaran')
            ->get()
            ->map(fn($m) => [
                'id' => $m->mata_pelajaran_id,
                'name' => $m->mataPelajaran->name,
                'mastery_percentage' => $m->mastery_percentage,
                'status' => $m->status,
                'assessments_completed' => $m->assessments_completed,
            ])
            ->toArray();
    }

    /**
     * Get all subjects user has mastered
     */
    public function getMasteredSubjects(User $user): array
    {
        return SubjectMastery::forUser($user->id)
            ->excellent()
            ->with('mataPelajaran')
            ->get()
            ->map(fn($m) => [
                'id' => $m->mata_pelajaran_id,
                'name' => $m->mataPelajaran->name,
                'mastery_percentage' => $m->mastery_percentage,
            ])
            ->toArray();
    }

    /**
     * Get overall mastery average for a user
     */
    public function getOverallMastery(User $user): float
    {
        $masteries = SubjectMastery::forUser($user->id)->get();

        if ($masteries->isEmpty()) {
            return 0;
        }

        return round($masteries->avg('mastery_percentage'), 2);
    }

    /**
     * Determine status based on mastery percentage
     */
    private function determineStatus(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'excellent',
            $percentage >= 60 => 'good',
            $percentage >= 40 => 'needs_help',
            default => 'poor'
        };
    }

    /**
     * Get recommendations for a subject
     */
    public function getRecommendations(User $user, MataPelajaran $subject): array
    {
        $mastery = SubjectMastery::where('user_id', $user->id)
            ->where('mata_pelajaran_id', $subject->id)
            ->first();

        if (!$mastery) {
            return [
                'action' => 'Mulai belajar mata pelajaran ini',
                'suggested_lessons' => [],
                'practice_frequency' => 'daily',
                'priority' => 'medium',
            ];
        }

        $recommendations = match ($mastery->status) {
            'excellent' => [
                'action' => 'Pertahankan performa Anda!',
                'suggested_lessons' => [],
                'practice_frequency' => 'weekly',
                'priority' => 'low',
            ],
            'good' => [
                'action' => 'Tingkatkan pemahaman Anda',
                'suggested_lessons' => [],
                'practice_frequency' => '3x per minggu',
                'priority' => 'low',
            ],
            'needs_help' => [
                'action' => 'Fokus pada pelajaran ini dengan intensif',
                'suggested_lessons' => [],
                'practice_frequency' => 'daily',
                'priority' => 'high',
            ],
            'poor' => [
                'action' => 'Pelajaran ini memerlukan perhatian URGENT',
                'suggested_lessons' => [],
                'practice_frequency' => 'multiple times per day',
                'priority' => 'critical',
            ],
        };

        $recommendations['mastery_percentage'] = $mastery->mastery_percentage;
        $recommendations['assessments_completed'] = $mastery->assessments_completed;

        return $recommendations;
    }
}
