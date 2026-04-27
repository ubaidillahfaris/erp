<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->enum('transaction_type', ['purchase', 'gift', 'adjustment'])->default('purchase');
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('signature_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
