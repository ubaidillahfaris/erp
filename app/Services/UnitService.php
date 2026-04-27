<?php

namespace App\Services;

use App\Models\UnitConversion;

class UnitService
{
    /**
     * Get the conversion ratio between two units.
     * Returns 1.0 if units are the same or no conversion pathway is found.
     * Logic: 1 unit of $fromUnitId = R units of $toUnitId
     */
    public function getConversionRatio($fromUnitId, $toUnitId, $productId = null): float
    {
        if ($fromUnitId == $toUnitId || ! $toUnitId || ! $fromUnitId) {
            return 1.0;
        }

        // 1. Try to find path using Product-Specific conversions
        if ($productId) {
            $ratio = $this->findRatio($fromUnitId, $toUnitId, $productId);
            if ($ratio !== null) {
                return $ratio;
            }
        }

        // 2. Fallback to Global conversions
        return $this->findRatio($fromUnitId, $toUnitId, null) ?? 1.0;
    }

    /**
     * Internal BFS to find ratio within a specific scope (product or global)
     */
    private function findRatio($fromUnitId, $toUnitId, $productId = null): ?float
    {
        $queue = [[$fromUnitId, 1.0]];
        $visited = [$fromUnitId];

        while (! empty($queue)) {
            [$currentId, $currentRatio] = array_shift($queue);

            if ($currentId == $toUnitId) {
                return $currentRatio;
            }

            // Direct conversions
            $directs = UnitConversion::where('unit_id', $currentId)
                ->where('product_id', $productId)
                ->get();
            foreach ($directs as $conv) {
                if (! in_array($conv->target_unit_id, $visited)) {
                    $visited[] = $conv->target_unit_id;
                    $queue[] = [$conv->target_unit_id, $currentRatio * (float) $conv->ratio];
                }
            }

            // Inverse conversions
            $inverses = UnitConversion::where('target_unit_id', $currentId)
                ->where('product_id', $productId)
                ->get();
            foreach ($inverses as $inv) {
                if ($inv->ratio != 0 && ! in_array($inv->unit_id, $visited)) {
                    $visited[] = $inv->unit_id;
                    $queue[] = [$inv->unit_id, $currentRatio * (1.0 / (float) $inv->ratio)];
                }
            }
        }

        return null;
    }
}
