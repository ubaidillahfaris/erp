<?php

namespace App\Services;

use App\Models\SatuanConversion;

class SatuanService
{
    /**
     * Get the conversion ratio between two units.
     * Returns 1.0 if units are the same or no conversion pathway is found.
     * Logic: 1 unit of $fromSatuanId = R units of $toSatuanId
     */
    public function getConversionRatio($fromSatuanId, $toSatuanId, $produkId = null): float
    {
        if ($fromSatuanId == $toSatuanId || !$toSatuanId || !$fromSatuanId) {
            return 1.0;
        }

        // 1. Try to find path using Product-Specific conversions
        if ($produkId) {
            $ratio = $this->findRatio($fromSatuanId, $toSatuanId, $produkId);
            if ($ratio !== null) {
                return $ratio;
            }
        }

        // 2. Fallback to Global conversions
        return $this->findRatio($fromSatuanId, $toSatuanId, null) ?? 1.0;
    }

    /**
     * Internal BFS to find ratio within a specific scope (product or global)
     */
    private function findRatio($fromSatuanId, $toSatuanId, $produkId = null): ?float
    {
        $queue = [[$fromSatuanId, 1.0]];
        $visited = [$fromSatuanId];

        while (!empty($queue)) {
            [$currentId, $currentRatio] = array_shift($queue);

            if ($currentId == $toSatuanId) {
                return $currentRatio;
            }

            // Direct conversions
            $directs = SatuanConversion::where('satuan_id', $currentId)
                ->where('produk_id', $produkId)
                ->get();
            foreach ($directs as $conv) {
                if (!in_array($conv->to_satuan_id, $visited)) {
                    $visited[] = $conv->to_satuan_id;
                    $queue[] = [$conv->to_satuan_id, $currentRatio * (float) $conv->rasio];
                }
            }

            // Inverse conversions
            $inverses = SatuanConversion::where('to_satuan_id', $currentId)
                ->where('produk_id', $produkId)
                ->get();
            foreach ($inverses as $inv) {
                if ($inv->rasio != 0 && !in_array($inv->satuan_id, $visited)) {
                    $visited[] = $inv->satuan_id;
                    $queue[] = [$inv->satuan_id, $currentRatio * (1.0 / (float) $inv->rasio)];
                }
            }
        }

        return null;
    }
}
