<?php

namespace App\Observers;

use App\Models\Production;
use App\Models\StockMovement;

class ProductionObserver
{
    /**
     * Handle the Production "deleted" event.
     */
    public function deleted(Production $production): void
    {
        $movements = StockMovement::whereIn('reference_type', ['production_usage', 'production_yield'])
            ->where('reference_id', $production->id)
            ->get();

        /** @var StockMovement $m */
        foreach ($movements as $m) {
            $m->delete();
        }
    }
}
