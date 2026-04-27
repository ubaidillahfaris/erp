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
        Schema::create('credit_note_items', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('credit_note_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('sale_item_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('product_id')->constrained();
            $blueprint->decimal('quantity_returned', 15, 3);
            $blueprint->decimal('unit_price', 15, 2);
            $blueprint->decimal('subtotal', 15, 2);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};
