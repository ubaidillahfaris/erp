<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old service order tables if exist to ensure clean state for new module
        Schema::dropIfExists('service_order_items');
        Schema::dropIfExists('service_orders');

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('service_category'); // laundry, cleaning, maintenance, etc.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['service_id', 'code']);
        });

        Schema::create('service_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->string('pricing_basis'); // per_kg, per_item, per_unit
            $table->string('unit_name');     // kg, pcs, m2, etc.
            $table->bigInteger('unit_price'); // integer cents
            $table->decimal('min_quantity', 15, 3)->nullable();
            $table->decimal('max_quantity', 15, 3)->nullable();
            $table->decimal('discount_pct', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_processing_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('status_code');
            $table->string('status_name');
            $table->integer('sequence_order')->default(0);
            $table->boolean('is_default_start')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
            $table->unique(['service_id', 'status_code']);
        });

        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('customer_type'); // customer, vendor
            $table->nullableMorphs('party'); // customer_id or vendor_id
            $table->date('order_date');
            $table->timestamp('completion_date')->nullable();
            $table->string('current_status_code');
            $table->text('notes')->nullable();
            $table->bigInteger('total_amount'); // integer cents
            $table->bigInteger('total_paid')->default(0); // integer cents
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->bigInteger('unit_price'); // snapshot, integer cents
            $table->decimal('discount_pct', 5, 2)->nullable();
            $table->bigInteger('subtotal'); // integer cents
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('payment_method');
            $table->bigInteger('amount'); // integer cents
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_payments');
        Schema::dropIfExists('service_order_items');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('service_processing_statuses');
        Schema::dropIfExists('service_pricings');
        Schema::dropIfExists('service_types');
        Schema::dropIfExists('services');
    }
};
