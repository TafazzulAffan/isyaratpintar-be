<?php

namespace Tests\Unit;

use App\Services\WaspasService;
use PHPUnit\Framework\TestCase;

class WaspasServiceTest extends TestCase
{
    private WaspasService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WaspasService();
    }

    public function test_calculates_waspas_scores_with_benefit_criteria(): void
    {
        $alternatives = [
            'student_a' => [
                'attendance' => 7,
                'performance' => 90,
                'engagement' => 1200,
                'subject_mastery' => 85,
            ],
            'student_b' => [
                'attendance' => 3,
                'performance' => 50,
                'engagement' => 300,
                'subject_mastery' => 40,
            ],
        ];

        $weights = [
            'attendance' => 0.20,
            'performance' => 0.30,
            'engagement' => 0.20,
            'subject_mastery' => 0.30,
        ];

        $criteriaTypes = [
            'attendance' => 'benefit',
            'performance' => 'benefit',
            'engagement' => 'benefit',
            'subject_mastery' => 'benefit',
        ];

        $results = $this->service->calculate($alternatives, $weights, $criteriaTypes, 0.5);

        $this->assertArrayHasKey('student_a', $results);
        $this->assertArrayHasKey('student_b', $results);

        $this->assertGreaterThan($results['student_b']['q'], $results['student_a']['q']);
        $this->assertSame(1, $results['student_a']['rank']);
        $this->assertSame(2, $results['student_b']['rank']);

        $this->assertGreaterThan(0, $results['student_a']['wsm']);
        $this->assertGreaterThan(0, $results['student_a']['wpm']);
        $this->assertEqualsWithDelta(
            0.5 * $results['student_a']['wsm'] + 0.5 * $results['student_a']['wpm'],
            $results['student_a']['q'],
            0.0001
        );
    }

    public function test_uses_benchmarks_when_only_one_alternative(): void
    {
        $alternatives = [
            'student_a' => [
                'attendance' => 5,
                'performance' => 80,
                'engagement' => 900,
                'subject_mastery' => 70,
            ],
        ];

        $weights = [
            'attendance' => 0.25,
            'performance' => 0.25,
            'engagement' => 0.25,
            'subject_mastery' => 0.25,
        ];

        $criteriaTypes = [
            'attendance' => 'benefit',
            'performance' => 'benefit',
            'engagement' => 'benefit',
            'subject_mastery' => 'benefit',
        ];

        $benchmarks = [
            'attendance' => ['min' => 0, 'max' => 7],
            'performance' => ['min' => 0, 'max' => 100],
            'engagement' => ['min' => 0, 'max' => 1800],
            'subject_mastery' => ['min' => 0, 'max' => 100],
        ];

        $results = $this->service->calculate(
            $alternatives,
            $weights,
            $criteriaTypes,
            0.5,
            $benchmarks
        );

        $this->assertEqualsWithDelta(5 / 7, $results['student_a']['normalized']['attendance'], 0.0001);
        $this->assertEqualsWithDelta(0.8, $results['student_a']['normalized']['performance'], 0.0001);
        $this->assertGreaterThan(0, $results['student_a']['q']);
        $this->assertSame(1, $results['student_a']['rank']);
    }

    /**
     * Test to validate system calculation against manual Excel calculation
     * This is useful for thesis validation.
     */
    public function test_validates_waspas_calculation_against_manual_calculation(): void
    {
        // 1. Setup Test Data (The 'Manual' Dataset)
        $alternatives = [
            'A1' => ['C1_attendance' => 6, 'C2_performance' => 85, 'C3_engagement' => 1000, 'C4_mastery' => 80],
            'A2' => ['C1_attendance' => 4, 'C2_performance' => 60, 'C3_engagement' => 600, 'C4_mastery' => 50],
            'A3' => ['C1_attendance' => 7, 'C2_performance' => 90, 'C3_engagement' => 1200, 'C4_mastery' => 85],
        ];

        $weights = ['C1_attendance' => 0.20, 'C2_performance' => 0.30, 'C3_engagement' => 0.20, 'C4_mastery' => 0.30];
        $criteriaTypes = ['C1_attendance' => 'benefit', 'C2_performance' => 'benefit', 'C3_engagement' => 'benefit', 'C4_mastery' => 'benefit'];

        // 2. Execute System Calculation
        $results = $this->service->calculate($alternatives, $weights, $criteriaTypes, 0.5);

        // --- OUTPUT UNTUK KEBUTUHAN SKRIPSI ---
        echo "\n\n==========================================================\n";
        echo "=== HASIL VALIDASI PERHITUNGAN WASPAS (SISTEM VS MANUAL) ===\n";
        echo "==========================================================\n\n";

        echo "1. MATRIKS KEPUTUSAN AWAL (X)\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("%-10s | %-10s | %-10s | %-10s | %-10s\n", 'Alt', 'C1(B)', 'C2(B)', 'C3(B)', 'C4(B)');
        foreach ($alternatives as $id => $vals) {
            echo sprintf("%-10s | %-10d | %-10d | %-10d | %-10d\n", $id, $vals['C1_attendance'], $vals['C2_performance'], $vals['C3_engagement'], $vals['C4_mastery']);
        }

        echo "\n2. BOBOT KRITERIA (W)\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("C1: %.2f, C2: %.2f, C3: %.2f, C4: %.2f\n", $weights['C1_attendance'], $weights['C2_performance'], $weights['C3_engagement'], $weights['C4_mastery']);

        echo "\n3. MATRIKS NORMALISASI\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("%-10s | %-10s | %-10s | %-10s | %-10s\n", 'Alt', 'C1', 'C2', 'C3', 'C4');
        foreach (['A1', 'A2', 'A3'] as $id) {
            $n = $results[$id]['normalized'];
            echo sprintf("%-10s | %-10.4f | %-10.4f | %-10.4f | %-10.4f\n", $id, $n['C1_attendance'], $n['C2_performance'], $n['C3_engagement'], $n['C4_mastery']);
        }

        echo "\n4. HASIL AKHIR WASPAS (WSM, WPM, Q) & PERANGKINGAN\n";
        echo "----------------------------------------------------------\n";
        echo sprintf("%-10s | %-10s | %-10s | %-10s | %-10s\n", 'Alt', 'WSM', 'WPM', 'Nilai Q', 'Rank');
        
        // Urutkan berdasarkan rank agar outputnya urut
        $sortedResults = collect($results)->sortBy('rank')->all();
        foreach ($sortedResults as $id => $res) {
            echo sprintf("%-10s | %-10.4f | %-10.4f | %-10.4f | %-10d\n", $id, $res['wsm'], $res['wpm'], $res['q'], $res['rank']);
        }
        echo "==========================================================\n\n";

        // 3. Assert Assertions against Manual Calculations
        $this->assertEqualsWithDelta(6/7, $results['A1']['normalized']['C1_attendance'], 0.0001);
        $this->assertEqualsWithDelta(85/90, $results['A1']['normalized']['C2_performance'], 0.0001);
        
        $expectedWsmA1 = (6/7 * 0.20) + (85/90 * 0.30) + (1000/1200 * 0.20) + (80/85 * 0.30);
        $this->assertEqualsWithDelta($expectedWsmA1, $results['A1']['wsm'], 0.0001);

        $expectedWpmA1 = pow(6/7, 0.20) * pow(85/90, 0.30) * pow(1000/1200, 0.20) * pow(80/85, 0.30);
        $this->assertEqualsWithDelta($expectedWpmA1, $results['A1']['wpm'], 0.0001);

        $expectedQA1 = (0.5 * $expectedWsmA1) + (0.5 * $expectedWpmA1);
        $this->assertEqualsWithDelta($expectedQA1, $results['A1']['q'], 0.0001);

        $this->assertEqualsWithDelta(1.0, $results['A3']['q'], 0.0001);
        
        $this->assertSame(1, $results['A3']['rank']);
        $this->assertSame(2, $results['A1']['rank']);
        $this->assertSame(3, $results['A2']['rank']);
    }
}
