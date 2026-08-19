<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KelasService extends BaseService
{
    public function getKelasForUser(User $user, int $pageSize = 15): LengthAwarePaginator
    {
        $query = Kelas::query()->with('guru')->withCount('siswa');

        if ($user->isAdmin()) {
            return $query->orderBy('nama')->paginate($pageSize);
        }

        if ($user->isGuru()) {
            return $query->where('guru_id', $user->id)->orderBy('nama')->paginate($pageSize);
        }

        return $user->kelasDiikuti()
            ->with('guru')
            ->withCount('siswa')
            ->orderBy('nama')
            ->paginate($pageSize);
    }

    public function getKelasById(Kelas $kelas): Kelas
    {
        return $kelas->load(['guru', 'siswa']);
    }

    public function createKelas(User $creator, array $data): Kelas
    {
        $guruId = $creator->id;

        if ($creator->isAdmin() && isset($data['guru_id'])) {
            $guruId = $data['guru_id'];
        }

        return Kelas::create([
            'nama' => $data['nama'],
            'guru_id' => $guruId,
        ])->load('guru');
    }

    public function updateKelas(Kelas $kelas, User $updater, array $data): Kelas
    {
        $updateData = ['nama' => $data['nama']];

        if ($updater->isAdmin() && isset($data['guru_id'])) {
            $updateData['guru_id'] = $data['guru_id'];
        }

        $kelas->update($updateData);

        return $kelas->fresh(['guru'])->loadCount('siswa');
    }

    public function deleteKelas(Kelas $kelas): void
    {
        $kelas->delete();
    }

    public function addStudents(Kelas $kelas, array $userIds): array
    {
        $existing = $kelas->siswa()->pluck('users.id')->toArray();

        $newIds    = array_diff($userIds, $existing);
        $skippedIds = array_intersect($userIds, $existing);

        if (!empty($newIds)) {
            $attachData = collect($newIds)
                ->mapWithKeys(fn ($id) => [$id => ['enrolled_at' => now()]])
                ->all();

            $kelas->siswa()->attach($attachData);
        }

        return [
            'kelas'    => $this->getKelasById($kelas),
            'added'    => count($newIds),
            'skipped'  => count($skippedIds),
        ];
    }

    public function removeStudent(Kelas $kelas, int $userId): Kelas
    {
        if (!$kelas->siswa()->where('users.id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['Siswa tidak terdaftar di kelas ini.'],
            ]);
        }

        $kelas->siswa()->detach($userId);

        return $this->getKelasById($kelas);
    }
}
