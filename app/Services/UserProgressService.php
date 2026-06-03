<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserLesson;
use App\Services\LessonService;
use Carbon\Carbon;

class UserProgressService extends BaseService
{
    public function __construct(
        private LessonService $lessonService
    ) {}

    public function markLessonAsCompleted(User $user, int $lessonId): UserLesson
    {
        Lesson::query()->findOrFail($lessonId);

        $progress = UserLesson::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
            ],
            [
                'completed' => true,
                'completed_at' => Carbon::now(),
            ]
        );

        if (!$progress->completed || $progress->completed_at === null) {
            $progress->completed = true;
            $progress->completed_at = Carbon::now();
            $progress->save();
        }

        return $progress;
    }

    public function getUserProgress(User $user): array
    {
        $mataPelajarans = $this->lessonService->getMataPelajaransWithLessons($user);

        $totalLessons = Lesson::query()->count();
        $completedLessons = UserLesson::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100, 2)
            : 0;

        return [
            'userId' => $user->id,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'progressPercentage' => $progressPercentage,
            'mataPelajarans' => $mataPelajarans,
        ];
    }
}
