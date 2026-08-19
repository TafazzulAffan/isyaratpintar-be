<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudentActivityLog;
use App\Models\SessionDuration;
use Illuminate\Support\Collection;

class StudentAnalyticsService
{
    public function __construct(
        private ActivityTrackingService $activityService
    ) {}

    /**
     * Get comprehensive learning statistics for a student
     */
    public function getLearningStatistics(User $user, int $days = 30): array
    {
        $totalLearningSeconds = $this->activityService->getTotalLearningTime($user, $days);
        $totalLearningMinutes = intval($totalLearningSeconds / 60);
        $avgSessionDuration = $this->activityService->getAverageSessionDuration($user, $days);

        $activityCounts = $this->activityService->getActivityTypeCounts($user, $days);
        $sessionStats = SessionDuration::forUser($user->id)
            ->completed()
            ->where('started_at', '>=', now()->subDays($days))
            ->get();

        return [
            'period_days' => $days,
            'total_learning_minutes' => $totalLearningMinutes,
            'total_learning_hours' => number_format($totalLearningMinutes / 60, 2),
            'total_sessions' => $sessionStats->count(),
            'average_session_duration_minutes' => number_format($avgSessionDuration / 60, 2),
            'total_activities' => StudentActivityLog::forUser($user->id)
                ->where('logged_at', '>=', now()->subDays($days))
                ->count(),
            'activities_by_type' => $this->formatActivityCounts($activityCounts),
            'learning_streak' => $this->calculateLearningStreak($user),
            'last_activity_at' => StudentActivityLog::forUser($user->id)
                ->latest('logged_at')
                ->first()?->logged_at,
        ];
    }

    /**
     * Get comprehensive engagement score for a student (0-100)
     */
    public function getEngagementScore(User $user, int $days = 30): int
    {
        $totalMinutes = intval($this->activityService->getTotalLearningTime($user, $days) / 60);
        
        // Score based on learning time in the period
        $timeScore = min(100, ($totalMinutes / 300) * 100); // 300 minutes = 100 score
        
        // Get activity frequency
        $activityCount = StudentActivityLog::forUser($user->id)
            ->where('logged_at', '>=', now()->subDays($days))
            ->count();
        $frequencyScore = min(100, ($activityCount / 50) * 100); // 50 activities = 100 score
        
        // Calculate average
        return intval(($timeScore + $frequencyScore) / 2);
    }

    /**
     * Calculate current learning streak in days
     */
    public function calculateLearningStreak(User $user): int
    {
        $streak = 0;
        $currentDate = now()->subDay();

        while (true) {
            $hasActivity = StudentActivityLog::forUser($user->id)
                ->whereBetween('logged_at', [
                    $currentDate->startOfDay(),
                    $currentDate->endOfDay(),
                ])
                ->exists();

            if ($hasActivity) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Format activity type counts with labels
     */
    private function formatActivityCounts(array $counts): array
    {
        $activityLabels = [
            'lesson_viewed' => 'Pelajaran Dilihat',
            'lesson_completed' => 'Pelajaran Selesai',
            'assessment_started' => 'Ujian Dimulai',
            'answer_submitted' => 'Jawaban Dikirim',
            'attempt_completed' => 'Ujian Selesai',
            'attempt_timed_out' => 'Ujian Habis Waktu',
            'session_started' => 'Sesi Dimulai',
            'session_ended' => 'Sesi Berakhir',
            'page_viewed' => 'Halaman Dilihat',
            'task_submitted' => 'Tugas Dikirim',
        ];

        $formatted = [];
        foreach ($counts as $type => $count) {
            $formatted[$type] = [
                'label' => $activityLabels[$type] ?? $type,
                'count' => $count,
            ];
        }

        return $formatted;
    }

    /**
     * Get students sorted by engagement score
     */
    public function getMostEngagedStudents(int $limit = 10, int $days = 30): Collection
    {
        $users = User::where('role', 'siswa')
            ->where('is_active', true)
            ->get();

        return $users->map(function (User $user) use ($days) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'engagement_score' => $this->getEngagementScore($user, $days),
                'total_learning_minutes' => intval($this->activityService->getTotalLearningTime($user, $days) / 60),
                'learning_streak' => $this->calculateLearningStreak($user),
            ];
        })->sortByDesc('engagement_score')
            ->take($limit);
    }

    /**
     * Get students with low engagement (needs attention)
     */
    public function getLowEngagementStudents(int $threshold = 20, int $days = 30): Collection
    {
        $users = User::where('role', 'siswa')
            ->where('is_active', true)
            ->get();

        return $users->filter(function (User $user) use ($threshold, $days) {
            return $this->getEngagementScore($user, $days) < $threshold;
        })->map(function (User $user) use ($days) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'engagement_score' => $this->getEngagementScore($user, $days),
                'last_activity_at' => StudentActivityLog::forUser($user->id)
                    ->latest('logged_at')
                    ->first()?->logged_at,
            ];
        });
    }
}
