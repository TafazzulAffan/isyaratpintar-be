<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PblCase;
use App\Models\User;
use App\Services\KelasAccessService;

class PblCasePolicy
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PblCase $pblCase): bool
    {
        return $this->kelasAccessService->canAccessContentWithKelas($user, $pblCase->kelas_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::GURU;
    }

    public function update(User $user, PblCase $pblCase): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isGuru() || !$pblCase->kelas_id) {
            return false;
        }

        return $pblCase->kelas?->guru_id === $user->id;
    }

    public function delete(User $user, PblCase $pblCase): bool
    {
        return $this->update($user, $pblCase);
    }
}
