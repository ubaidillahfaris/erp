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
        Schema::create('credit_notes', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('credit_note_number')->unique();
            $blueprint->foreignId('sale_id')->constrained()->onDelete('cascade');
            $blueprint->string('status')->default('draft'); // draft, posted, voided
            $blueprint->text('reason')->nullable();
            $blueprint->decimal('total_amount', 15, 2)->default(0);
            $blueprint->timestamp('posted_at')->nullable();
            $blueprint->foreignId('created_by')->constrained('users');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
