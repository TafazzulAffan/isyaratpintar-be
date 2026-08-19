<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use App\Services\KelasAccessService;

class LessonPolicy
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $this->kelasAccessService->canAccessContentWithKelas($user, $lesson->kelas_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::GURU;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isGuru() || !$lesson->kelas_id) {
            return false;
        }

        return $lesson->kelas?->guru_id === $user->id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
