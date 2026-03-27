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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->date('tanggal');
            $table->foreignId('bom_id')->constrained('boms')->onDelete('restrict');
            $table->foreignId('produk_id')->constrained('produks')->onDelete('restrict');
            $table->decimal('target_yield', 8, 2);
            $table->decimal('actual_yield', 8, 2)->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
