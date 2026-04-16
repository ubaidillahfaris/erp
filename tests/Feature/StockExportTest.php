<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Produk;
use App\Models\Satuan;
use App\Jobs\GenerateStockMutationPdfJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StockExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_dispatch_mutation_pdf_job(): void
    {
        Queue::fake();

        $user = User::factory()->superadmin()->create();
        
        $response = $this->actingAs($user)->post(route('stock.export-pdf'), [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(GenerateStockMutationPdfJob::class, function ($job) {
            return true; // We could check filters here if needed
        });
    }
}
