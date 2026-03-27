<?php

namespace Tests\Unit;

use App\Models\Satuan;
use App\Models\SatuanConversion;
use App\Services\SatuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SatuanServiceTest extends TestCase
{
    use RefreshDatabase;

    private SatuanService $service;
    private Satuan $pcs, $box, $carton;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SatuanService();

        $this->pcs = Satuan::create(['nama' => 'Pieces', 'simbol' => 'pcs']);
        $this->box = Satuan::create(['nama' => 'Box', 'simbol' => 'box']);
        $this->carton = Satuan::create(['nama' => 'Carton', 'simbol' => 'ctn']);
    }

    public function test_same_unit_returns_ratio_one(): void
    {
        $ratio = $this->service->getConversionRatio($this->pcs->id, $this->pcs->id);
        $this->assertEquals(1.0, $ratio);
    }

    public function test_direct_conversion(): void
    {
        // 1 box = 12 pcs
        SatuanConversion::create([
            'satuan_id' => $this->box->id,
            'to_satuan_id' => $this->pcs->id,
            'rasio' => 12
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
        SatuanConversion::create([
            'satuan_id' => $this->carton->id,
            'to_satuan_id' => $this->box->id,
            'rasio' => 4
        ]);
        SatuanConversion::create([
            'satuan_id' => $this->box->id,
            'to_satuan_id' => $this->pcs->id,
            'rasio' => 12
        ]);

        $this->assertEquals(48.0, $this->service->getConversionRatio($this->carton->id, $this->pcs->id));
        $this->assertEquals(1 / 48, $this->service->getConversionRatio($this->pcs->id, $this->carton->id));
    }

    public function test_product_specific_priority(): void
    {
        $produk = \App\Models\Produk::factory()->create();

        // Global: 1 box = 10 pcs
        SatuanConversion::create([
            'satuan_id' => $this->box->id,
            'to_satuan_id' => $this->pcs->id,
            'rasio' => 10,
            'produk_id' => null
        ]);

        // Product Specific: 1 box = 12 pcs
        SatuanConversion::create([
            'satuan_id' => $this->box->id,
            'to_satuan_id' => $this->pcs->id,
            'rasio' => 12,
            'produk_id' => $produk->id
        ]);

        // Without product -> global (10)
        $this->assertEquals(10.0, $this->service->getConversionRatio($this->box->id, $this->pcs->id));
        
        // With product -> specific (12)
        $this->assertEquals(12.0, $this->service->getConversionRatio($this->box->id, $this->pcs->id, $produk->id));
    }

    public function test_missing_path_returns_one(): void
    {
        $other = Satuan::create(['nama' => 'Kilogram', 'simbol' => 'kg']);
        
        $ratio = $this->service->getConversionRatio($this->pcs->id, $other->id);
        $this->assertEquals(1.0, $ratio);
    }
}
