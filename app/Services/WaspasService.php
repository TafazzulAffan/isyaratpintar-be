<?php

namespace App\Services;

class WaspasService
{
    /**
     * Calculate WASPAS scores for a set of alternatives.
     *
     * @param  array<string, array<string, float>>  $alternatives  ['id' => ['criterion' => value]]
     * @param  array<string, float>  $weights  ['criterion' => weight]
     * @param  array<string, string>  $criteriaTypes  ['criterion' => 'benefit'|'cost']
     * @param  array<string, array{min: float, max: float}>|null  $benchmarks
     * @return array<string, array{
     *     wsm: float,
     *     wpm: float,
     *     q: float,
     *     normalized: array<string, float>,
     *     rank: int
     * }>
     */
    public function calculate(
        array $alternatives,
        array $weights,
        array $criteriaTypes,
        float $lambda = 0.5,
        ?array $benchmarks = null,
    ): array {
        if (empty($alternatives)) {
            return [];
        }

        $criteria = array_keys($weights);
        $normalizedMatrix = $this->normalizeMatrix(
            $alternatives,
            $criteria,
            $criteriaTypes,
            $benchmarks,
        );

        $results = [];

        foreach ($alternatives as $alternativeId => $values) {
            $wsm = 0.0;
            $wpm = 1.0;

            foreach ($criteria as $criterion) {
                $weight = $weights[$criterion];
                $normalized = $normalizedMatrix[$alternativeId][$criterion];

                $wsm += $weight * $normalized;
                $wpm *= pow($normalized, $weight);
            }

            $q = ($lambda * $wsm) + ((1 - $lambda) * $wpm);

            $results[$alternativeId] = [
                'wsm' => round($wsm, 6),
                'wpm' => round($wpm, 6),
                'q' => round($q, 6),
                'normalized' => $normalizedMatrix[$alternativeId],
            ];
        }

        return $this->assignRanks($results);
    }

    /**
     * @param  array<string, array<string, float>>  $alternatives
     * @param  array<int, string>  $criteria
     * @param  array<string, string>  $criteriaTypes
     * @param  array<string, array{min: float, max: float}>|null  $benchmarks
     * @return array<string, array<string, float>>
     */
    private function normalizeMatrix(
        array $alternatives,
        array $criteria,
        array $criteriaTypes,
        ?array $benchmarks,
    ): array {
        $normalized = [];
        $useBenchmarks = count($alternatives) < 2 && $benchmarks !== null;

        foreach ($criteria as $criterion) {
            $values = array_map(
                fn (array $row) => max(0, (float) ($row[$criterion] ?? 0)),
                $alternatives,
            );

            if ($benchmarks !== null && isset($benchmarks[$criterion])) {
                $max = (float) $benchmarks[$criterion]['max'];
                $min = (float) $benchmarks[$criterion]['min'];
            } else {
                $max = max($values) ?: 1.0;
                $min = min($values);

                if ($min === $max) {
                    $min = 0.0;
                }
            }

            foreach ($alternatives as $alternativeId => $row) {
                $value = max(0, (float) ($row[$criterion] ?? 0));
                $type = $criteriaTypes[$criterion] ?? 'benefit';

                if ($type === 'benefit') {
                    $normalized[$alternativeId][$criterion] = $max > 0
                        ? min(1.0, $value / $max)
                        : 0.0;
                } else {
                    // For cost criteria, invert the scale using (max - value) / max
                    // This avoids the 0/value bug when the min benchmark is 0
                    $normalized[$alternativeId][$criterion] = $max > 0
                        ? max(0.0, ($max - $value) / $max)
                        : 0.0;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, array{wsm: float, wpm: float, q: float, normalized: array<string, float>}>  $results
     * @return array<string, array{wsm: float, wpm: float, q: float, normalized: array<string, float>, rank: int}>
     */
    private function assignRanks(array $results): array
    {
        uasort($results, fn (array $a, array $b) => $b['q'] <=> $a['q']);

        $rank = 1;
        foreach ($results as $alternativeId => $result) {
            $results[$alternativeId]['rank'] = $rank++;
        }

        return $results;
    }
}
