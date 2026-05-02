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
        Schema::create('production_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_step_id')->nullable()->constrained('production_steps')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->integer('sequence_order')->default(0);
            $table->boolean('is_start')->default(false);
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('color_hex')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // Drop the old table as requested to replace it with production steps
        Schema::dropIfExists('service_processing_statuses');
    }

    public function down(): void
    {
        Schema::dropIfExists('production_steps');
    }
};
