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
        Schema::create('employees', function (Blueprint $row) {
            $row->id();
            $row->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $row->string('name');
            $row->string('nik')->unique();
            $row->string('email')->nullable();
            $row->string('phone')->nullable();
            $row->text('address')->nullable();
            $row->string('position');
            $row->string('department');
            $row->date('join_date');
            $row->string('employment_type'); // Tetap, Kontrak, Harian
            $row->string('status')->default('active'); // active, inactive
            $row->decimal('basic_salary', 15, 2);
            $row->string('bank_name')->nullable();
            $row->string('bank_account')->nullable();
            $row->string('photo_path')->nullable();
            $row->timestamps();
            $row->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
