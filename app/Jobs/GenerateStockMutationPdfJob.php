<?php

namespace App\Jobs;

use App\Models\StockMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class GenerateStockMutationPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $filters = []
    ) {}

    public function handle(): void
    {
        $query = StockMovement::with(['produk', 'satuan'])->latest();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['produk_id'])) {
            $query->where('produk_id', $this->filters['produk_id']);
        }

        $movements = $query->get();

        $filename = 'stock_mutation_' . now()->format('Ymd_His') . '.pdf';
        
        $pdf = Pdf::view('pdf.stock-mutation', [
            'movements' => $movements,
            'filters' => $this->filters,
            'generated_at' => now(),
        ]);

        $directory = 'reports/mutations';
        if (!Storage::disk('private')->exists($directory)) {
            Storage::disk('private')->makeDirectory($directory);
        }

        Storage::disk('private')->put($directory . '/' . $filename, $pdf->generatePdfContent());
    }
}
