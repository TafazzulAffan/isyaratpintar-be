<?php

namespace Tests\Unit;

use App\Services\PedagogicalActionService;
use App\Services\WaspasService;
use PHPUnit\Framework\TestCase;

class PedagogicalActionServiceTest extends TestCase
{
    private PedagogicalActionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // PedagogicalActionService needs config() which requires Laravel app context.
        // We boot the app for config access.
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $this->service = new PedagogicalActionService(new WaspasService());
    }

    public function test_returns_six_ranked_actions(): void
    {
        $rawCriteria = [
            'attendance' => 5,
            'performance' => 70,
            'engagement' => 500,
            'subject_mastery' => 60,
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        $this->assertArrayHasKey('recommended', $result);
        $this->assertArrayHasKey('all_actions', $result);
        $this->assertArrayHasKey('raw_criteria', $result);

        // Should have 6 actions
        $this->assertCount(6, $result['all_actions']);

        // Ranks should be 1-6
        $ranks = array_column($result['all_actions'], 'rank');
        $this->assertEquals([1, 2, 3, 4, 5, 6], $ranks);

        // Recommended should be rank 1
        $this->assertSame(1, $result['recommended']['rank']);

        // All actions should have required fields
        foreach ($result['all_actions'] as $action) {
            $this->assertArrayHasKey('code', $action);
            $this->assertArrayHasKey('label', $action);
            $this->assertArrayHasKey('q_score', $action);
            $this->assertArrayHasKey('wsm', $action);
            $this->assertArrayHasKey('wpm', $action);
            $this->assertArrayHasKey('rank', $action);
        }

        // Recommended should have reasoning
        $this->assertArrayHasKey('reasoning', $result['recommended']);
        $this->assertNotEmpty($result['recommended']['reasoning']);
    }

    public function test_all_low_criteria_recommends_bimbingan_individual(): void
    {
        // Student with ALL low criteria → should recommend "Bimbingan Individual"
        // because that action uses cost-type for all criteria (low = better match)
        $rawCriteria = [
            'attendance' => 1,       // very low (max 7)
            'performance' => 15,     // very low (max 100)
            'engagement' => 30,      // very low (max 1800 minutes)
            'subject_mastery' => 10, // very low (max 100)
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        $this->assertSame(
            'bimbingan_individual',
            $result['recommended']['code'],
            'Student with all low criteria should get bimbingan_individual as top recommendation'
        );
    }

    public function test_all_high_criteria_recommends_pengayaan(): void
    {
        // Student with ALL high criteria → should recommend "Pengayaan & Tantangan"
        // because that action uses benefit-type for all criteria (high = better match)
        $rawCriteria = [
            'attendance' => 7,        // max
            'performance' => 95,      // near max
            'engagement' => 1500,     // high
            'subject_mastery' => 92,  // near max
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        $this->assertSame(
            'pengayaan_tantangan',
            $result['recommended']['code'],
            'Student with all high criteria should get pengayaan_tantangan as top recommendation'
        );
    }

    public function test_high_engagement_low_mastery_recommends_penguatan_materi(): void
    {
        // Student who is actively learning (high engagement, high attendance)
        // but mastery and performance are low → needs "Penguatan Materi"
        $rawCriteria = [
            'attendance' => 6,        // high
            'performance' => 30,      // low
            'engagement' => 1200,     // high
            'subject_mastery' => 25,  // low
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        $this->assertSame(
            'penguatan_materi',
            $result['recommended']['code'],
            'Student with high engagement but low mastery should get penguatan_materi'
        );
    }

    public function test_high_attendance_low_engagement_recommends_variasi_metode(): void
    {
        // Student who attends (high attendance) and has decent scores
        // but engagement is very low → might be bored, needs "Variasi Metode"
        $rawCriteria = [
            'attendance' => 6,       // high
            'performance' => 65,     // decent
            'engagement' => 50,      // very low
            'subject_mastery' => 60, // decent
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        $this->assertSame(
            'variasi_metode',
            $result['recommended']['code'],
            'Student with good attendance but low engagement should get variasi_metode'
        );
    }

    /**
     * Validation test: output detailed WASPAS calculation for thesis documentation.
     */
    public function test_validates_pedagogical_action_calculation_for_thesis(): void
    {
        $rawCriteria = [
            'attendance' => 5,
            'performance' => 60,
            'engagement' => 400,
            'subject_mastery' => 50,
        ];

        $result = $this->service->calculateForStudent($rawCriteria);

        echo "\n\n==========================================================\n";
        echo "=== VALIDASI SPK REKOMENDASI TINDAKAN PEDAGOGIS (WASPAS) ===\n";
        echo "==========================================================\n\n";

        echo "1. PROFIL KRITERIA SISWA\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("%-20s : %s / 7 hari\n", 'Kehadiran', $rawCriteria['attendance']);
        echo sprintf("%-20s : %s / 100\n", 'Performa', $rawCriteria['performance']);
        echo sprintf("%-20s : %s / 1800 menit\n", 'Engagement', $rawCriteria['engagement']);
        echo sprintf("%-20s : %s / 100%%\n", 'Penguasaan Materi', $rawCriteria['subject_mastery']);

        echo "\n2. RANKING TINDAKAN PEDAGOGIS (WASPAS)\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("%-5s | %-30s | %-10s | %-10s | %-10s\n", 'Rank', 'Tindakan', 'WSM', 'WPM', 'Skor Q');
        echo str_repeat('-', 75) . "\n";

        foreach ($result['all_actions'] as $action) {
            echo sprintf(
                "%-5d | %-30s | %-10.4f | %-10.4f | %-10.4f\n",
                $action['rank'],
                $action['label'],
                $action['wsm'],
                $action['wpm'],
                $action['q_score']
            );
        }

        echo "\n3. REKOMENDASI TINDAKAN UTAMA\n";
        echo "----------------------------------------------------------\n";
        echo "Tindakan  : " . $result['recommended']['label'] . "\n";
        echo "Skor Q    : " . $result['recommended']['q_score'] . "\n";
        echo "Deskripsi : " . $result['recommended']['description'] . "\n";
        echo "Alasan    : " . $result['recommended']['reasoning'] . "\n";
        echo "==========================================================\n\n";

        // Basic assertions
        $this->assertCount(6, $result['all_actions']);
        $this->assertSame(1, $result['recommended']['rank']);
        $this->assertGreaterThan(0, $result['recommended']['q_score']);
    }
}
