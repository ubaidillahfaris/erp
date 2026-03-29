<?php

namespace Tests\Feature;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SatuanConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_satuan_with_conversions(): void
    {
        $user = User::factory()->superadmin()->create();
        $satuan = Satuan::create([
            'nama' => 'Lusin',
            'simbol' => 'lsn',
        ]);

        $pcs = Satuan::create([
            'nama' => 'Pieces',
            'simbol' => 'pcs',
        ]);

        $response = $this->actingAs($user)
            ->put(route('satuan.update', $satuan), [
                'nama' => 'Lusin Updated',
                'simbol' => 'lsn',
                'conversions' => [
                    [
                        'to_satuan_id' => $pcs->id,
                        'rasio' => 12,
                    ],
                ],
            ]);

        $response->assertRedirect(route('satuan.index'));
        $this->assertDatabaseHas('satuan_conversions', [
            'satuan_id' => $satuan->id,
            'to_satuan_id' => $pcs->id,
            'rasio' => 12.0000,
        ]);
    }

    public function test_can_create_satuan_with_conversions(): void
    {
        $user = User::factory()->superadmin()->create();
        $pcs = Satuan::create([
            'nama' => 'Pieces',
            'simbol' => 'pcs',
        ]);

        $response = $this->actingAs($user)
            ->post(route('satuan.store'), [
                'nama' => 'Lusin',
                'simbol' => 'lsn',
                'conversions' => [
                    [
                        'to_satuan_id' => $pcs->id,
                        'rasio' => 12,
                    ],
                ],
            ]);

        $response->assertRedirect(route('satuan.index'));

        $satuan = Satuan::where('nama', 'Lusin')->first();
        $this->assertNotNull($satuan);

        $this->assertDatabaseHas('satuan_conversions', [
            'satuan_id' => $satuan->id,
            'to_satuan_id' => $pcs->id,
            'rasio' => 12.0000,
        ]);
    }
}
