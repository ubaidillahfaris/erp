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
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_id')->constrained('payables')->onDelete('cascade');
            $table->date('reminder_date');
            $table->integer('remind_before_days')->default(3);
            $table->string('channel'); // whatsapp|email|system
            $table->string('status')->default('pending'); // pending|sent|failed
            $table->timestamp('sent_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
