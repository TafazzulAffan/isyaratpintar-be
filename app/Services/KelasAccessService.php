<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class KelasAccessService
{
    public function scopeContentQuery(Builder|Relation $query, ?User $user): Builder|Relation
    {
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        if ($user->isGuru()) {
            return $query->whereHas('kelas', fn (Builder $q) => $q->where('guru_id', $user->id));
        }

        $kelasIds = $user->kelasDiikuti()->pluck('kelas.id');

        return $query->whereIn('kelas_id', $kelasIds);
    }

    public function canAccessKelas(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $kelas->guru_id === $user->id;
        }

        return $user->kelasDiikuti()->where('kelas.id', $kelas->id)->exists();
    }

    public function canAccessContentWithKelas(?User $user, ?int $kelasId): bool
    {
        if (!$kelasId) {
            return $user?->isAdmin() ?? false;
        }

        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $kelas = Kelas::find($kelasId);

        if (!$kelas) {
            return false;
        }

        return $this->canAccessKelas($user, $kelas);
    }
}
