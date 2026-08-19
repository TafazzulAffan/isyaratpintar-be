<?php

namespace App\Services;

use App\Models\StudentActivityLog;
use App\Models\SessionDuration;
use App\Models\User;
use Illuminate\Pagination\Paginator;

class ActivityTrackingService
{
    /**
     * Get paginated activity log for a user
     */
    public function getUserActivityLog(
        User $user,
        ?string $activityType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 25
    ): Paginator {
        $query = StudentActivityLog::forUser($user->id);

        if ($activityType) {
            $query->byType($activityType);
        }

        if ($startDate && $endDate) {
            $query->betweenDates(
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            );
        }

        return $query->latest('logged_at')->simplePaginate($perPage);
    }

    /**
     * Get all activity types with their counts for a user
     */
    public function getActivityTypeCounts(User $user, int $days = 7): array
    {
        return StudentActivityLog::forUser($user->id)
            ->recent($days)
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();
    }

    /**
     * Get activity summary for a user
     */
    public function getActivitySummary(User $user, int $days = 7): array
    {
        $logs = StudentActivityLog::forUser($user->id)->recent($days)->get();

        return [
            'total_activities' => $logs->count(),
            'by_type' => $logs->groupBy('activity_type')->map->count(),
            'first_activity_at' => $logs->min('logged_at'),
            'last_activity_at' => $logs->max('logged_at'),
        ];
    }

    /**
     * Get session history for a user
     */
    public function getUserSessionHistory(
        User $user,
        ?string $sessionType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 25
    ): Paginator {
        $query = SessionDuration::forUser($user->id)->completed();

        if ($sessionType) {
            $query->byType($sessionType);
        }

        if ($startDate && $endDate) {
            $query->betweenDates(
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            );
        }

        return $query->latest('started_at')->simplePaginate($perPage);
    }

    /**
     * Get total learning time for a user
     */
    public function getTotalLearningTime(User $user, int $days = null): int
    {
        $completedQuery = SessionDuration::forUser($user->id)->completed();
        $activeQuery = SessionDuration::forUser($user->id)->active();

        if ($days) {
            $completedQuery->where('started_at', '>=', now()->subDays($days));
            $activeQuery->where('started_at', '>=', now()->subDays($days));
        }

        $completedSeconds = (int) $completedQuery->sum('duration_seconds');
        
        $activeSeconds = $activeQuery->get()->sum(function ($session) {
            return now()->diffInSeconds($session->started_at);
        });

        return $completedSeconds + (int) $activeSeconds;
    }

    /**
     * Get average session duration for a user
     */
    public function getAverageSessionDuration(User $user, int $days = 7): float
    {
        $sessions = SessionDuration::forUser($user->id)
            ->completed()
            ->recent($days)
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        return $sessions->avg('duration_seconds');
    }

    /**
     * Get learning time breakdown by day for charting
     */
    public function getDailyLearningBreakdown(User $user, int $days = 30): array
    {
        $sessions = SessionDuration::forUser($user->id)
            ->completed()
            ->where('started_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(started_at) as date, SUM(duration_seconds) as total_seconds')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total_seconds', 'date')
            ->toArray();

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[$date] = isset($sessions[$date]) ? intval($sessions[$date] / 60) : 0; // Convert to minutes
        }

        return $result;
    }

    /**
     * Get activity breakdown by type for charting
     */
    public function getActivityBreakdownByType(User $user, int $days = 7): array
    {
        return StudentActivityLog::forUser($user->id)
            ->recent($days)
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();
    }

    /**
     * Get most active hours for a user
     */
    public function getMostActiveHours(User $user, int $days = 7): array
    {
        return StudentActivityLog::forUser($user->id)
            ->recent($days)
            ->selectRaw('HOUR(logged_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Check if user is currently active
     */
    public function isUserActive(User $user, int $minutesThreshold = 30): bool
    {
        $lastActivity = StudentActivityLog::forUser($user->id)
            ->latest('logged_at')
            ->first();

        if (!$lastActivity) {
            return false;
        }

        return $lastActivity->logged_at->diffInMinutes(now()) < $minutesThreshold;
    }
}
