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
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tier_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tier_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->unique(['tier_id', 'feature_key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tier_features');
        Schema::dropIfExists('tiers');
    }
};
