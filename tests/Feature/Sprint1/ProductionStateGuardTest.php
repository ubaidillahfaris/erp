<?php

namespace Tests\Feature\Sprint1;

use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionStateGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage products']);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('manage products');
    }

    /** @test */
    public function test_completed_production_cannot_be_updated()
    {
        $production = Production::factory()->create([
            'status' => 'completed',
        ]);

        $item = ProductionItem::factory()->create([
            'production_id' => $production->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('production.update', $production), [
                'actual_yield' => 100,
                'items' => [
                    [
                        'id' => $item->id,
                        'produk_id' => $item->produk_id,
                        'satuan_id' => $item->satuan_id,
                        'actual_qty' => 10,
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }
}
