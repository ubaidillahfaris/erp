<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\UnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnitService $service;

    private Unit $pcs;

    private Unit $box;

    private Unit $carton;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UnitService;

        $this->pcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs']);
        $this->box = Unit::create(['name' => 'Box', 'symbol' => 'box']);
        $this->carton = Unit::create(['name' => 'Carton', 'symbol' => 'ctn']);
    }

    public function test_same_unit_returns_ratio_one(): void
    {
        $ratio = $this->service->getConversionRatio($this->pcs->id, $this->pcs->id);
        $this->assertEquals(1.0, $ratio);
    }

    public function test_direct_conversion(): void
    {
        // 1 box = 12 pcs
        UnitConversion::create([
            'unit_id' => $this->box->id,
            'target_unit_id' => $this->pcs->id,
            'ratio' => 12,
        ]);

        // 1 box to pcs -> should be 12
        $this->assertEquals(12.0, $this->service->getConversionRatio($this->box->id, $this->pcs->id));

        // 1 pcs to box -> should be 1/12
        $this->assertEquals(1 / 12, $this->service->getConversionRatio($this->pcs->id, $this->box->id));
    }

    public function test_multi_step_conversion(): void
    {
        // 1 carton = 4 boxes
        // 1 box = 12 pcs
        // Result: 1 carton = 48 pcs
        UnitConversion::create([
            'unit_id' => $this->carton->id,
            'target_unit_id' => $this->box->id,
            'ratio' => 4,
        ]);
        UnitConversion::create([
            'unit_id' => $this->box->id,
            'target_unit_id' => $this->pcs->id,
            'ratio' => 12,
        ]);

        $this->assertEquals(48.0, $this->service->getConversionRatio($this->carton->id, $this->pcs->id));
        $this->assertEquals(1 / 48, $this->service->getConversionRatio($this->pcs->id, $this->carton->id));
    }

    public function test_product_specific_priority(): void
    {
        $product = Product::factory()->create();

        // Global: 1 box = 10 pcs
        UnitConversion::create([
            'unit_id' => $this->box->id,
            'target_unit_id' => $this->pcs->id,
            'ratio' => 10,
            'product_id' => null,
        ]);

        // Product Specific: 1 box = 12 pcs
        UnitConversion::create([
            'unit_id' => $this->box->id,
            'target_unit_id' => $this->pcs->id,
            'ratio' => 12,
            'product_id' => $product->id,
        ]);

        // Without product -> global (10)
        $this->assertEquals(10.0, $this->service->getConversionRatio($this->box->id, $this->pcs->id));

        // With product -> specific (12)
        $this->assertEquals(12.0, $this->service->getConversionRatio($this->box->id, $this->pcs->id, $product->id));
    }

    public function test_missing_path_returns_one(): void
    {
        $other = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);

        $ratio = $this->service->getConversionRatio($this->pcs->id, $other->id);
        $this->assertEquals(1.0, $ratio);
    }
}
