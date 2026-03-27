<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->unique()->constrained('produks')->onDelete('cascade');
            $table->foreignId('last_satuan_id')->constrained('satuans')->onDelete('cascade');
            $table->decimal('balance', 15, 4)->default(0);
            $table->foreignId('last_movement_id')->nullable()->constrained('stock_movements')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
