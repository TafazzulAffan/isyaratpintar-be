<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\MataPelajaran;
use App\Models\SessionDuration;
use App\Models\StudentActivityLog;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\TeacherInsight;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SPKDummyDataSeeder extends Seeder
{
    /**
     * 10 siswa dummy dengan data SPK yang terkontrol.
     *
     * Setiap siswa memiliki profil yang sudah dirancang agar menghasilkan
     * distribusi risiko: 4 Low, 2 Medium, 1 High, 3 Critical.
     */
    private array $studentProfiles = [
        // [name, email, attendance_days, performance_scores, engagement_minutes, mastery_per_subject]
        [
            'name' => 'Aisyah Putri Rahmawati',
            'email' => 'aisyah@siswa.isyaratpintar.id',
            'attendance_days' => 7,  // aktif setiap hari
            'performance_scores' => [95, 88, 90, 92, 94, 91, 89, 93, 90, 98], // rata-rata ~92
            'engagement_total_minutes' => 1500,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 95,
                'Matematika Dasar' => 82,
                'Ilmu Pengetahuan Alam (IPA)' => 88,
                'Bahasa Indonesia' => 90,
                'Ilmu Pengetahuan Sosial (IPS)' => 85,
            ],
        ],
        [
            'name' => 'Bayu Adi Pratama',
            'email' => 'bayu@siswa.isyaratpintar.id',
            'attendance_days' => 2,
            'performance_scores' => [35, 42, 30, 38, 45, 28, 40, 38, 42, 32], // rata-rata ~38
            'engagement_total_minutes' => 60,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 35,
                'Matematika Dasar' => 18,
                'Ilmu Pengetahuan Alam (IPA)' => 22,
                'Bahasa Indonesia' => 40,
                'Ilmu Pengetahuan Sosial (IPS)' => 25,
            ],
        ],
        [
            'name' => 'Citra Dewi Lestari',
            'email' => 'citra@siswa.isyaratpintar.id',
            'attendance_days' => 6,
            'performance_scores' => [72, 78, 70, 75, 80, 73, 76, 74, 72, 80], // rata-rata ~75
            'engagement_total_minutes' => 840,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 78,
                'Matematika Dasar' => 62,
                'Ilmu Pengetahuan Alam (IPA)' => 68,
                'Bahasa Indonesia' => 75,
                'Ilmu Pengetahuan Sosial (IPS)' => 67,
            ],
        ],
        [
            'name' => 'Dimas Arya Nugraha',
            'email' => 'dimas@siswa.isyaratpintar.id',
            'attendance_days' => 1,
            'performance_scores' => [40, 45, 38, 42, 48, 35, 44, 40, 42, 46], // rata-rata ~42
            'engagement_total_minutes' => 30,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 42,
                'Matematika Dasar' => 20,
                'Ilmu Pengetahuan Alam (IPA)' => 28,
                'Bahasa Indonesia' => 45,
                'Ilmu Pengetahuan Sosial (IPS)' => 25,
            ],
        ],
        [
            'name' => 'Eka Sari Wulandari',
            'email' => 'eka@siswa.isyaratpintar.id',
            'attendance_days' => 5,
            'performance_scores' => [62, 68, 60, 65, 70, 63, 67, 64, 66, 65], // rata-rata ~65
            'engagement_total_minutes' => 420,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 68,
                'Matematika Dasar' => 48,
                'Ilmu Pengetahuan Alam (IPA)' => 55,
                'Bahasa Indonesia' => 65,
                'Ilmu Pengetahuan Sosial (IPS)' => 54,
            ],
        ],
        [
            'name' => 'Farhan Maulana Akbar',
            'email' => 'farhan@siswa.isyaratpintar.id',
            'attendance_days' => 4,
            'performance_scores' => [52, 58, 50, 55, 60, 53, 56, 54, 55, 57], // rata-rata ~55
            'engagement_total_minutes' => 300,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 58,
                'Matematika Dasar' => 38,
                'Ilmu Pengetahuan Alam (IPA)' => 45,
                'Bahasa Indonesia' => 55,
                'Ilmu Pengetahuan Sosial (IPS)' => 44,
            ],
        ],
        [
            'name' => 'Galuh Permata Sari',
            'email' => 'galuh@siswa.isyaratpintar.id',
            'attendance_days' => 7,
            'performance_scores' => [82, 88, 80, 85, 90, 83, 86, 84, 88, 84], // rata-rata ~85
            'engagement_total_minutes' => 1200,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 90,
                'Matematika Dasar' => 75,
                'Ilmu Pengetahuan Alam (IPA)' => 82,
                'Bahasa Indonesia' => 88,
                'Ilmu Pengetahuan Sosial (IPS)' => 75,
            ],
        ],
        [
            'name' => 'Hendra Wijaya Kusuma',
            'email' => 'hendra@siswa.isyaratpintar.id',
            'attendance_days' => 3,
            'performance_scores' => [48, 52, 45, 50, 55, 47, 51, 49, 52, 51], // rata-rata ~50
            'engagement_total_minutes' => 180,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 52,
                'Matematika Dasar' => 32,
                'Ilmu Pengetahuan Alam (IPA)' => 40,
                'Bahasa Indonesia' => 50,
                'Ilmu Pengetahuan Sosial (IPS)' => 36,
            ],
        ],
        [
            'name' => 'Indah Nur Fitriani',
            'email' => 'indah@siswa.isyaratpintar.id',
            'attendance_days' => 6,
            'performance_scores' => [75, 80, 74, 78, 82, 76, 79, 77, 78, 81], // rata-rata ~78
            'engagement_total_minutes' => 960,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 82,
                'Matematika Dasar' => 68,
                'Ilmu Pengetahuan Alam (IPA)' => 72,
                'Bahasa Indonesia' => 78,
                'Ilmu Pengetahuan Sosial (IPS)' => 70,
            ],
        ],
        [
            'name' => 'Joko Susanto Prasetyo',
            'email' => 'joko@siswa.isyaratpintar.id',
            'attendance_days' => 0,
            'performance_scores' => [22, 28, 20, 25, 30, 18, 26, 24, 28, 29], // rata-rata ~25
            'engagement_total_minutes' => 15,
            'mastery' => [
                'Bahasa Isyarat Indonesia (BISINDO)' => 25,
                'Matematika Dasar' => 10,
                'Ilmu Pengetahuan Alam (IPA)' => 15,
                'Bahasa Indonesia' => 28,
                'Ilmu Pengetahuan Sosial (IPS)' => 12,
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding SPK Dummy Data (10 siswa terkontrol)...');
        $this->command->newLine();

        // Step 1: Pastikan mata pelajaran ada
        $mataPelajaranMap = $this->ensureMataPelajaran();

        // Step 2: Buat/ambil user siswa
        $students = $this->ensureStudents();

        // Step 3: Seed data per siswa
        foreach ($students as $index => $student) {
            $profile = $this->studentProfiles[$index];
            $this->command->info("📝 [{$student->id}] {$student->name}");

            $this->seedAttendance($student, $profile['attendance_days']);
            $this->seedEngagement($student, $profile['engagement_total_minutes']);
            $this->seedPerformance($student, $profile['performance_scores'], $mataPelajaranMap);
            $this->seedSubjectMastery($student, $profile['mastery'], $mataPelajaranMap);
        }

        // Step 4: Hitung risk profiles menggunakan WASPAS
        $this->command->newLine();
        $this->command->info('🔄 Menghitung WASPAS Risk Profiles...');
        $this->calculateRiskProfiles();

        $this->command->newLine();
        $this->command->info('✅ SPK Dummy Data seeding selesai!');
        $this->command->info('   Jalankan "php artisan tinker" lalu "StudentRiskProfile::with(\'user\')->orderByDesc(\'overall_risk_score\')->get()" untuk melihat hasil.');
    }

    /**
     * Pastikan 5 mata pelajaran ada di database.
     */
    private function ensureMataPelajaran(): array
    {
        $subjects = [
            'Bahasa Isyarat Indonesia (BISINDO)',
            'Matematika Dasar',
            'Ilmu Pengetahuan Alam (IPA)',
            'Bahasa Indonesia',
            'Ilmu Pengetahuan Sosial (IPS)',
        ];

        $map = [];
        foreach ($subjects as $name) {
            $mp = MataPelajaran::firstOrCreate(['name' => $name]);
            $map[$name] = $mp->id;
        }

        $this->command->info("📚 {$this->count($map)} mata pelajaran tersedia.");
        return $map;
    }

    /**
     * Buat atau ambil 10 user siswa.
     */
    private function ensureStudents(): array
    {
        $students = [];
        foreach ($this->studentProfiles as $profile) {
            $student = User::firstOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'username' => explode('@', $profile['email'])[0],
                    'password' => Hash::make('password123'),
                    'role' => UserRole::SISWA,
                    'is_active' => true,
                ]
            );

            // Update name jika sudah ada tapi nama berbeda
            if ($student->name !== $profile['name']) {
                $student->update(['name' => $profile['name']]);
            }

            $students[] = $student;
        }

        $this->command->info("👥 {$this->count($students)} siswa siap.");
        return $students;
    }

    /**
     * Seed attendance: login activity di N hari unik dalam 7 hari terakhir.
     */
    private function seedAttendance(User $student, int $activeDays): void
    {
        StudentActivityLog::where('user_id', $student->id)->delete();

        if ($activeDays <= 0) {
            $this->command->comment("   ⤷ Attendance: 0 hari (tidak aktif)");
            return;
        }

        // Pilih N hari unik dari 7 hari terakhir
        $availableDays = range(0, 6);
        shuffle($availableDays);
        $selectedDays = array_slice($availableDays, 0, min($activeDays, 7));

        foreach ($selectedDays as $daysAgo) {
            $date = Carbon::now()->subDays($daysAgo);

            // Login activity
            StudentActivityLog::create([
                'user_id' => $student->id,
                'activity_type' => 'session_started',
                'logged_at' => $date->copy()->setTime(rand(7, 10), rand(0, 59)),
            ]);

            // Learning activity
            StudentActivityLog::create([
                'user_id' => $student->id,
                'activity_type' => 'lesson_viewed',
                'logged_at' => $date->copy()->setTime(rand(10, 14), rand(0, 59)),
            ]);

            // Assessment activity (tidak setiap hari)
            if (rand(0, 1)) {
                StudentActivityLog::create([
                    'user_id' => $student->id,
                    'activity_type' => 'attempt_completed',
                    'logged_at' => $date->copy()->setTime(rand(14, 16), rand(0, 59)),
                ]);
            }
        }

        $this->command->comment("   ⤷ Attendance: {$activeDays} hari aktif");
    }

    /**
     * Seed engagement: sesi belajar dengan total durasi terkontrol.
     */
    private function seedEngagement(User $student, int $totalMinutes): void
    {
        SessionDuration::where('user_id', $student->id)->delete();

        if ($totalMinutes <= 0) {
            $this->command->comment("   ⤷ Engagement: 0 menit");
            return;
        }

        // Bagi total menit ke beberapa sesi dalam 30 hari
        $remainingMinutes = $totalMinutes;
        $sessionsCount = max(1, intval($totalMinutes / 45)); // rata-rata 45 menit per sesi
        $sessionsCount = min($sessionsCount, 40); // maksimal 40 sesi

        for ($i = 0; $i < $sessionsCount && $remainingMinutes > 0; $i++) {
            $avgPerSession = $remainingMinutes / ($sessionsCount - $i);
            $durationMinutes = max(5, intval($avgPerSession + rand(-10, 10)));
            $durationMinutes = min($durationMinutes, $remainingMinutes);

            $daysAgo = rand(0, 29);
            $startHour = rand(8, 18);
            $startMinute = rand(0, 59);
            $startedAt = Carbon::now()->subDays($daysAgo)->setTime($startHour, $startMinute);
            $endedAt = $startedAt->copy()->addMinutes($durationMinutes);

            SessionDuration::create([
                'user_id' => $student->id,
                'session_type' => 'course_learning',
                'duration_seconds' => $durationMinutes * 60,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);

            $remainingMinutes -= $durationMinutes;
        }

        $this->command->comment("   ⤷ Engagement: {$totalMinutes} menit ({$sessionsCount} sesi)");
    }

    /**
     * Seed performance: assessment attempts dengan skor terkontrol.
     *
     * Database memiliki unique constraint: (user_id, assessment_id, status).
     * Sehingga setiap siswa hanya boleh punya 1 COMPLETED attempt per assessment.
     * Solusi: buat 10 dummy assessment unik (1 per skor).
     */
    private function seedPerformance(User $student, array $scores, array $mataPelajaranMap): void
    {
        AssessmentAttempt::where('user_id', $student->id)->delete();

        $mataPelajaranIds = array_values($mataPelajaranMap);

        // Pastikan ada minimal 10 assessment unik (1 per skor)
        $dummyAssessments = $this->ensureDummyAssessments(count($scores), $mataPelajaranIds);

        foreach ($scores as $i => $score) {
            $daysAgo = rand(1, 30);
            $startedAt = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 15), rand(0, 59));

            AssessmentAttempt::create([
                'user_id' => $student->id,
                'assessment_id' => $dummyAssessments[$i]->id,
                'status' => 'COMPLETED',
                'score' => $score,
                'level' => AssessmentAttempt::determineLevel($score),
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes(rand(10, 25)),
            ]);
        }

        $avgScore = round(array_sum($scores) / count($scores), 1);
        $this->command->comment("   ⤷ Performance: {$avgScore} avg ({$this->count($scores)} attempts)");
    }

    /**
     * Pastikan ada N dummy assessments unik yang bisa dipakai seeding.
     */
    private function ensureDummyAssessments(int $count, array $mataPelajaranIds): array
    {
        $assessments = [];
        for ($i = 0; $i < $count; $i++) {
            $mpId = $mataPelajaranIds[$i % count($mataPelajaranIds)];
            $assessments[] = Assessment::firstOrCreate(
                ['slug' => 'spk-dummy-assessment-' . ($i + 1)],
                [
                    'title' => 'Ujian SPK Dummy ' . ($i + 1),
                    'description' => 'Assessment dummy untuk seeding SPK',
                    'time_limit' => 30,
                    'mata_pelajaran_id' => $mpId,
                ]
            );
        }
        return $assessments;
    }

    /**
     * Seed subject mastery per mata pelajaran.
     */
    private function seedSubjectMastery(User $student, array $masteryData, array $mataPelajaranMap): void
    {
        SubjectMastery::where('user_id', $student->id)->delete();

        foreach ($masteryData as $subjectName => $percentage) {
            $mpId = $mataPelajaranMap[$subjectName] ?? null;
            if (!$mpId) {
                continue;
            }

            SubjectMastery::create([
                'user_id' => $student->id,
                'mata_pelajaran_id' => $mpId,
                'mastery_percentage' => $percentage,
                'average_score' => $percentage,
                'assessments_completed' => rand(3, 10),
                'status' => $this->determineMasteryStatus($percentage),
                'last_assessed_at' => now()->subDays(rand(0, 7)),
                'calculated_at' => now(),
            ]);
        }

        $avgMastery = round(array_sum($masteryData) / count($masteryData), 1);
        $this->command->comment("   ⤷ Subject Mastery: {$avgMastery}% avg ({$this->count($masteryData)} mapel)");
    }

    /**
     * Hitung risk profiles menggunakan service WASPAS.
     */
    private function calculateRiskProfiles(): void
    {
        // Hapus risk profiles lama untuk siswa dummy
        $dummyEmails = array_column($this->studentProfiles, 'email');
        $dummyUserIds = User::whereIn('email', $dummyEmails)->pluck('id');

        StudentRiskProfile::whereIn('user_id', $dummyUserIds)->delete();
        TeacherInsight::whereIn('student_id', $dummyUserIds)->delete();

        // Gunakan StudentRiskProfileService untuk menghitung semua
        try {
            $service = app(\App\Services\StudentRiskProfileService::class);
            $profiles = $service->calculateAllRiskProfiles();

            // Tampilkan hasil
            $this->command->newLine();
            $this->command->info('📊 Hasil Perhitungan WASPAS:');
            $this->command->table(
                ['Rank', 'Nama', 'Skor Q', 'Skor Risiko', 'Level', 'Rekomendasi'],
                collect($profiles)
                    ->filter(fn($p) => in_array($p->user_id, $dummyUserIds->toArray()))
                    ->sortByDesc('overall_risk_score')
                    ->reverse()
                    ->values()
                    ->map(function ($profile, $index) {
                        $actionRec = $profile->recommendations['_action_recommendation'] ?? null;
                        $recommended = $actionRec['recommended'] ?? null;

                        $levelEmoji = match ($profile->risk_level) {
                            'critical' => '🔴',
                            'high' => '🟠',
                            'medium' => '🟡',
                            'low' => '🟢',
                            default => '⚪',
                        };

                        return [
                            $index + 1,
                            $profile->user->name,
                            round($profile->waspas_score ?? (1 - $profile->overall_risk_score / 100), 4),
                            round($profile->overall_risk_score, 2),
                            "{$levelEmoji} {$profile->risk_level}",
                            $recommended['label'] ?? '-',
                        ];
                    })
                    ->toArray()
            );
        } catch (\Throwable $e) {
            $this->command->error("⚠️  Error saat menghitung WASPAS: {$e->getMessage()}");
            $this->command->comment('   Anda bisa menghitung manual dengan: php artisan tinker → app(StudentRiskProfileService::class)->calculateAllRiskProfiles()');
        }
    }

    private function determineMasteryStatus(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'excellent',
            $percentage >= 60 => 'good',
            $percentage >= 40 => 'needs_help',
            default => 'poor',
        };
    }

    private function count(array $arr): int
    {
        return count($arr);
    }
}
