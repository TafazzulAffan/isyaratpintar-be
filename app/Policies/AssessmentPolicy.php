<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\User;
use App\Services\KelasAccessService;

class AssessmentPolicy
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $this->kelasAccessService->canAccessContentWithKelas($user, $assessment->kelas_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::GURU;
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isGuru() || !$assessment->kelas_id) {
            return false;
        }

        return $assessment->kelas?->guru_id === $user->id;
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    public function viewResults(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::GURU;
    }
}
