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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('type', ['debit', 'kredit']);
            $table->decimal('amount', 15, 2);
            $table->string('category'); // pembelian, beban, penjualan, dll
            $table->string('payment_method')->nullable(); // tunai, transfer, qris, hutang
            $table->nullableMorphs('reference');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
