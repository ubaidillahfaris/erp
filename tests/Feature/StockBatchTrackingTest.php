<?php

namespace Tests\Feature;

use App\Actions\RecordStockMovement;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBatchTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_correctly_drains_batches_using_fefo_logic()
    {
        // Setup dependencies manually to avoid factory issues
        $warehouse = Warehouse::create([
            'name' => 'Default Warehouse',
            'code' => 'WH001',
            'is_default' => true,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'General',
            'slug' => 'general'
        ]);

        $unit = Unit::create([
            'name' => 'pcs',
            'is_base' => true
        ]);

        $product = Product::create([
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_batch_tracked' => true,
            'track_stock' => true,
        ]);

        // 1. Create Batch A (Earlier Expiry)
        StockBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-A',
            'expiry_date' => now()->addDays(10),
            'quantity_on_hand' => 10,
            'unit_id' => $product->unit_id,
            'received_at' => now()->subDays(2),
        ]);

        // 2. Create Batch B (Later Expiry)
        StockBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'BATCH-B',
            'expiry_date' => now()->addDays(20),
            'quantity_on_hand' => 10,
            'unit_id' => $product->unit_id,
            'received_at' => now()->subDays(1),
        ]);

        // 3. Perform Outbound Movement of 15
        $action = new RecordStockMovement();
        $action->handle([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $product->unit_id,
            'type' => 'out',
            'quantity' => 15,
            'reference_type' => 'test',
            'reference_id' => 1,
            'notes' => 'Test deduction',
        ]);

        // 4. Verify Batch A is empty
        $batchA = StockBatch::where('batch_number', 'BATCH-A')->first();
        $this->assertEquals(0, (float) $batchA->quantity_on_hand);

        // 5. Verify Batch B has 5 left
        $batchB = StockBatch::where('batch_number', 'BATCH-B')->first();
        $this->assertEquals(5, (float) $batchB->quantity_on_hand);
    }
}
