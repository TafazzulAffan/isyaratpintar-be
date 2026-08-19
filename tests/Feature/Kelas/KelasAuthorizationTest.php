<?php

namespace Tests\Feature\Kelas;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KelasAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(UserRole $role, string $suffix): User
    {
        return User::create([
            'name' => ucfirst($role->value) . ' ' . $suffix,
            'email' => $role->value . $suffix . '@test.com',
            'username' => $role->value . $suffix,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_guru_can_create_kelas(): void
    {
        $guru = $this->createUser(UserRole::GURU, '1');
        Sanctum::actingAs($guru);

        $response = $this->postJson('/api/kelas', ['nama' => 'X IPA 1']);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'X IPA 1');

        $this->assertDatabaseHas('kelas', [
            'nama' => 'X IPA 1',
            'guru_id' => $guru->id,
        ]);
    }

    public function test_guru_cannot_view_other_guru_kelas(): void
    {
        $guruA = $this->createUser(UserRole::GURU, 'a');
        $guruB = $this->createUser(UserRole::GURU, 'b');

        $kelas = Kelas::create([
            'nama' => 'Kelas A',
            'guru_id' => $guruA->id,
        ]);

        Sanctum::actingAs($guruB);

        $this->getJson('/api/kelas/' . $kelas->id)->assertForbidden();
    }

    public function test_admin_can_view_all_kelas(): void
    {
        $admin = $this->createUser(UserRole::ADMIN, '1');
        $guru = $this->createUser(UserRole::GURU, 'owner');

        $kelas = Kelas::create([
            'nama' => 'Kelas Guru',
            'guru_id' => $guru->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/kelas/' . $kelas->id)
            ->assertOk()
            ->assertJsonPath('data.nama', 'Kelas Guru');
    }

    public function test_guru_can_add_student_to_kelas(): void
    {
        $guru = $this->createUser(UserRole::GURU, '1');
        $siswa = $this->createUser(UserRole::SISWA, '1');

        $kelas = Kelas::create([
            'nama' => 'X IPA 2',
            'guru_id' => $guru->id,
        ]);

        Sanctum::actingAs($guru);

        $this->postJson('/api/kelas/' . $kelas->id . '/siswa', [
            'user_ids' => [$siswa->id],
        ])->assertOk();

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas->id,
            'user_id' => $siswa->id,
        ]);
    }

    public function test_siswa_without_kelas_sees_empty_lessons(): void
    {
        $siswa = $this->createUser(UserRole::SISWA, 'lonely');
        $guru = $this->createUser(UserRole::GURU, '1');
        $mataPelajaran = MataPelajaran::create(['name' => 'Matematika']);

        $kelas = Kelas::create([
            'nama' => 'Kelas Terisolasi',
            'guru_id' => $guru->id,
        ]);

        \App\Models\Lesson::create([
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mataPelajaran->id,
            'title' => 'Materi Rahasia',
            'description' => 'Hanya untuk anggota kelas',
        ]);

        Sanctum::actingAs($siswa);

        $response = $this->getJson('/api/lessons/all');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_enrolled_siswa_can_see_kelas_lessons(): void
    {
        $guru = $this->createUser(UserRole::GURU, '1');
        $siswa = $this->createUser(UserRole::SISWA, '1');
        $mataPelajaran = MataPelajaran::create(['name' => 'IPA']);

        $kelas = Kelas::create([
            'nama' => 'X IPA 3',
            'guru_id' => $guru->id,
        ]);

        $kelas->siswa()->attach($siswa->id, ['enrolled_at' => now()]);

        \App\Models\Lesson::create([
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mataPelajaran->id,
            'title' => 'Materi Kelas',
            'description' => 'Untuk siswa terdaftar',
        ]);

        Sanctum::actingAs($siswa);

        $response = $this->getJson('/api/lessons/all');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Materi Kelas', $response->json('data.0.title'));
    }

    public function test_admin_can_create_kelas_with_assigned_guru(): void
    {
        $admin = $this->createUser(UserRole::ADMIN, '1');
        $guru = $this->createUser(UserRole::GURU, '2');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/kelas', [
            'nama' => 'X IPA 4',
            'guru_id' => $guru->id
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'X IPA 4')
            ->assertJsonPath('data.guru.id', $guru->id);

        $this->assertDatabaseHas('kelas', [
            'nama' => 'X IPA 4',
            'guru_id' => $guru->id,
        ]);
    }

    public function test_admin_can_change_kelas_guru(): void
    {
        $admin = $this->createUser(UserRole::ADMIN, '1');
        $guru1 = $this->createUser(UserRole::GURU, '1');
        $guru2 = $this->createUser(UserRole::GURU, '2');

        $kelas = Kelas::create([
            'nama' => 'X IPA 5',
            'guru_id' => $guru1->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/kelas/' . $kelas->id, [
            'nama' => 'X IPA 5 Updated',
            'guru_id' => $guru2->id
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nama', 'X IPA 5 Updated')
            ->assertJsonPath('data.guru.id', $guru2->id);

        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'nama' => 'X IPA 5 Updated',
            'guru_id' => $guru2->id,
        ]);
    }

    public function test_non_admin_cannot_assign_kelas_to_other_guru(): void
    {
        $guru1 = $this->createUser(UserRole::GURU, '1');
        $guru2 = $this->createUser(UserRole::GURU, '2');

        Sanctum::actingAs($guru1);

        $response = $this->postJson('/api/kelas', [
            'nama' => 'X IPA 6',
            'guru_id' => $guru2->id
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'X IPA 6')
            ->assertJsonPath('data.guru.id', $guru1->id);

        $this->assertDatabaseHas('kelas', [
            'nama' => 'X IPA 6',
            'guru_id' => $guru1->id,
        ]);
    }
}
