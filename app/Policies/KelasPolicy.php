<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;
use App\Services\KelasAccessService;

class KelasPolicy
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru() || $user->isSiswa();
    }

    public function view(User $user, Kelas $kelas): bool
    {
        return $this->kelasAccessService->canAccessKelas($user, $kelas);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $user->isAdmin() || ($user->isGuru() && $kelas->guru_id === $user->id);
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->isAdmin() || ($user->isGuru() && $kelas->guru_id === $user->id);
    }

    public function manageStudents(User $user, Kelas $kelas): bool
    {
        return $user->isAdmin() || ($user->isGuru() && $kelas->guru_id === $user->id);
    }
}
