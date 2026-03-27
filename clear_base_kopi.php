<?php
use Illuminate\Support\Facades\DB;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\Stock;

$baseKopiId = 6;

// Get all production IDs for Base Kopi
$prodIds = Production::where('produk_id', $baseKopiId)->pluck('id')->toArray();

if (!empty($prodIds)) {
    // Delete related stock movements (both the 'in' for base kopi and 'out' for ingredients)
    StockMovement::where('reference_type', 'production')
        ->whereIn('reference_id', $prodIds)
        ->delete();
    
    // Delete productions
    Production::whereIn('id', $prodIds)->delete();
}

// Recalculate stock for Base Kopi
$baseKopiStock = Stock::firstOrCreate(['produk_id' => $baseKopiId]);
$baseKopiQty = StockMovement::where('produk_id', $baseKopiId)->sum('quantity');
$baseKopiStock->update(['current_qty' => $baseKopiQty]);

// Recalculate stock for its ingredients (Air, Kopi Bubuk - IDs 4 and 5)
foreach ([4, 5] as $ingId) {
    $st = Stock::firstOrCreate(['produk_id' => $ingId]);
    $qty = StockMovement::where('produk_id', $ingId)->sum('quantity');
    $st->update(['current_qty' => $qty]);
}

echo "Base Kopi production and stock movement data cleared successfully.\n";
