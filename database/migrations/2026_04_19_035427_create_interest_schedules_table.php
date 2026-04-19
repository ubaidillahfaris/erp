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
        Schema::create('interest_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_id')->constrained('payables')->onDelete('cascade');
            $table->integer('period_number');
            $table->date('due_date');
            $table->decimal('principal_portion', 15, 2);
            $table->decimal('interest_portion', 15, 2);
            $table->decimal('total_due', 15, 2);
            $table->string('status')->default('pending'); // pending|paid|overdue
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interest_schedules');
    }
};
