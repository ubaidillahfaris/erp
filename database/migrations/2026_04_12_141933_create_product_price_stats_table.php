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
        Schema::create('product_price_stats', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained()->onDelete('cascade');
            $table->foreignId('satuan_id')->constrained()->onDelete('cascade');
            $table->decimal('avg_price', 15, 2)->default(0);
            $table->decimal('min_price', 15, 2)->default(0);
            $table->decimal('max_price', 15, 2)->default(0);
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['produk_id', 'satuan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_stats');
    }
};
