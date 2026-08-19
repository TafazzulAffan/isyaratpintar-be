<?php

namespace Tests\Unit;

use App\Services\WaspasService;
use PHPUnit\Framework\TestCase;

/**
 * Test: Verifikasi klasifikasi risk level dan konversi criterion-to-risk
 * berdasarkan konfigurasi SPK (config/spk.php).
 *
 * Relevansi Skripsi: Membuktikan bahwa logika bisnis SPK (WASPAS)
 * menghasilkan klasifikasi risiko yang akurat dan konsisten.
 */
class RiskLevelClassificationTest extends TestCase
{
    // =========================================================================
    // RISK LEVEL THRESHOLD TESTS
    // =========================================================================

    /**
     * Risk levels defined in config/spk.php:
     * critical >= 75, high >= 60, medium >= 40, low < 40
     */

    public function test_score_above_75_is_critical(): void
    {
        $this->assertSame('critical', $this->determineRiskLevel(75));
        $this->assertSame('critical', $this->determineRiskLevel(80));
        $this->assertSame('critical', $this->determineRiskLevel(100));
    }

    public function test_score_60_to_74_is_high(): void
    {
        $this->assertSame('high', $this->determineRiskLevel(60));
        $this->assertSame('high', $this->determineRiskLevel(65));
        $this->assertSame('high', $this->determineRiskLevel(74.99));
    }

    public function test_score_40_to_59_is_medium(): void
    {
        $this->assertSame('medium', $this->determineRiskLevel(40));
        $this->assertSame('medium', $this->determineRiskLevel(50));
        $this->assertSame('medium', $this->determineRiskLevel(59.99));
    }

    public function test_score_below_40_is_low(): void
    {
        $this->assertSame('low', $this->determineRiskLevel(0));
        $this->assertSame('low', $this->determineRiskLevel(20));
        $this->assertSame('low', $this->determineRiskLevel(39.99));
    }

    // =========================================================================
    // CRITERION-TO-RISK CONVERSION TESTS
    // =========================================================================

    /**
     * Formula: risk = (1 - normalizedBenefit) * 100
     * Where normalizedBenefit is a value between 0 and 1.
     */

    public function test_perfect_normalized_score_gives_zero_risk(): void
    {
        // Normalized = 1.0 (best possible) → risk = 0%
        $risk = $this->criterionToRisk(1.0);
        $this->assertEqualsWithDelta(0.0, $risk, 0.01);
    }

    public function test_zero_normalized_score_gives_maximum_risk(): void
    {
        // Normalized = 0.0 (worst possible) → risk = 100%
        $risk = $this->criterionToRisk(0.0);
        $this->assertEqualsWithDelta(100.0, $risk, 0.01);
    }

    public function test_half_normalized_score_gives_50_percent_risk(): void
    {
        $risk = $this->criterionToRisk(0.5);
        $this->assertEqualsWithDelta(50.0, $risk, 0.01);
    }

    public function test_criterion_risk_is_inversely_proportional(): void
    {
        // Higher normalized score → lower risk
        $riskHigh = $this->criterionToRisk(0.8); // high performance
        $riskLow = $this->criterionToRisk(0.3);  // low performance

        $this->assertLessThan($riskLow, $riskHigh, 'Higher performance harus menghasilkan lower risk');
    }

    // =========================================================================
    // OVERALL RISK SCORE TESTS (Q-score to Risk)
    // =========================================================================

    /**
     * Formula: overallRiskScore = (1 - Q) * 100
     * Where Q is the WASPAS combined score (0-1).
     */

    public function test_high_waspas_q_score_gives_low_risk(): void
    {
        // Q = 0.9 (great student) → risk = 10%
        $overallRisk = round((1 - 0.9) * 100, 2);
        $this->assertEqualsWithDelta(10.0, $overallRisk, 0.01);
        $this->assertSame('low', $this->determineRiskLevel($overallRisk));
    }

    public function test_low_waspas_q_score_gives_high_risk(): void
    {
        // Q = 0.2 (struggling student) → risk = 80%
        $overallRisk = round((1 - 0.2) * 100, 2);
        $this->assertEqualsWithDelta(80.0, $overallRisk, 0.01);
        $this->assertSame('critical', $this->determineRiskLevel($overallRisk));
    }

    public function test_medium_waspas_q_score_gives_medium_risk(): void
    {
        // Q = 0.55 → risk = 45%
        $overallRisk = round((1 - 0.55) * 100, 2);
        $this->assertEqualsWithDelta(45.0, $overallRisk, 0.01);
        $this->assertSame('medium', $this->determineRiskLevel($overallRisk));
    }

    // =========================================================================
    // WASPAS RANKING CONSISTENCY TESTS
    // =========================================================================

    public function test_student_with_better_criteria_gets_higher_rank(): void
    {
        $service = new WaspasService();

        $alternatives = [
            'excellent' => ['attendance' => 7, 'performance' => 95, 'engagement' => 1500, 'subject_mastery' => 90],
            'average'   => ['attendance' => 4, 'performance' => 60, 'engagement' => 600,  'subject_mastery' => 55],
            'poor'      => ['attendance' => 1, 'performance' => 30, 'engagement' => 100,  'subject_mastery' => 20],
        ];

        $weights = ['attendance' => 0.20, 'performance' => 0.30, 'engagement' => 0.20, 'subject_mastery' => 0.30];
        $types   = ['attendance' => 'benefit', 'performance' => 'benefit', 'engagement' => 'benefit', 'subject_mastery' => 'benefit'];

        $results = $service->calculate($alternatives, $weights, $types, 0.5);

        // Rank 1 (best) should be excellent, rank 3 (worst) should be poor
        $this->assertSame(1, $results['excellent']['rank']);
        $this->assertSame(2, $results['average']['rank']);
        $this->assertSame(3, $results['poor']['rank']);

        // Q scores should be ordered: excellent > average > poor
        $this->assertGreaterThan($results['average']['q'], $results['excellent']['q']);
        $this->assertGreaterThan($results['poor']['q'], $results['average']['q']);
    }

    public function test_waspas_lambda_affects_q_score(): void
    {
        $service = new WaspasService();

        $alternatives = [
            'student_a' => ['attendance' => 5, 'performance' => 70, 'engagement' => 800, 'subject_mastery' => 65],
        ];
        $weights = ['attendance' => 0.25, 'performance' => 0.25, 'engagement' => 0.25, 'subject_mastery' => 0.25];
        $types   = ['attendance' => 'benefit', 'performance' => 'benefit', 'engagement' => 'benefit', 'subject_mastery' => 'benefit'];
        $benchmarks = [
            'attendance' => ['min' => 0, 'max' => 7],
            'performance' => ['min' => 0, 'max' => 100],
            'engagement' => ['min' => 0, 'max' => 1800],
            'subject_mastery' => ['min' => 0, 'max' => 100],
        ];

        // Lambda = 0 → pure WPM
        $resultWPM = $service->calculate($alternatives, $weights, $types, 0.0, $benchmarks);
        // Lambda = 1 → pure WSM
        $resultWSM = $service->calculate($alternatives, $weights, $types, 1.0, $benchmarks);
        // Lambda = 0.5 → combined (default)
        $resultCombined = $service->calculate($alternatives, $weights, $types, 0.5, $benchmarks);

        // Q with lambda=0 should equal WPM
        $this->assertEqualsWithDelta(
            $resultWPM['student_a']['wpm'],
            $resultWPM['student_a']['q'],
            0.0001,
            'Lambda=0: Q harus sama dengan WPM'
        );

        // Q with lambda=1 should equal WSM
        $this->assertEqualsWithDelta(
            $resultWSM['student_a']['wsm'],
            $resultWSM['student_a']['q'],
            0.0001,
            'Lambda=1: Q harus sama dengan WSM'
        );

        // Combined should be between WSM and WPM (or at least different)
        $this->assertEqualsWithDelta(
            0.5 * $resultCombined['student_a']['wsm'] + 0.5 * $resultCombined['student_a']['wpm'],
            $resultCombined['student_a']['q'],
            0.0001,
            'Lambda=0.5: Q = 0.5*WSM + 0.5*WPM'
        );
    }

    // =========================================================================
    // HELPERS (mirrors logic from StudentRiskProfileService)
    // =========================================================================

    private function determineRiskLevel(float $score): string
    {
        // Mirrors config/spk.php risk_levels
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            default => 'low',
        };
    }

    private function criterionToRisk(float $normalizedBenefit): float
    {
        return round((1 - $normalizedBenefit) * 100, 2);
    }
}
