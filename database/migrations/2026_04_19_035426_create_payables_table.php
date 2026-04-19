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
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // payable|receivable
            $table->string('reference_type'); // restock|sale|nasabah
            $table->unsignedBigInteger('reference_id');
            $table->string('party_type'); // vendor|customer
            $table->unsignedBigInteger('party_id');
            $table->decimal('principal_amount', 15, 2);
            $table->string('interest_type')->nullable(); // flat|percentage
            $table->decimal('interest_rate', 10, 4)->nullable();
            $table->string('interest_period')->nullable(); // daily|weekly|monthly
            $table->integer('installment_count')->nullable();
            $table->decimal('total_interest', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->date('due_date')->nullable();
            $table->string('status')->default('open'); // open|partial|paid|overdue
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['party_type', 'party_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
