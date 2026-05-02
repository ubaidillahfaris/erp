<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->unique(['code', 'company_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('boms', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_credit_settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_price_histories', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_prices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_statuses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customer_types', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('depreciation_schedules', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('financial_summaries', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('interest_schedules', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('inventory_dispositions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('job_batches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('menu_role', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('module_role', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('nasabah_statuses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('period_locks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('product_price_stats', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('purchase_attachments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('restock_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('sale_customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('boms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_credit_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_price_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_statuses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customer_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('depreciation_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('financial_summaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('interest_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('inventory_dispositions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('job_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('menu_role', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('module_role', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('nasabah_statuses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('payment_reminders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('period_locks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('product_price_stats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('purchase_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('restock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('restocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('sale_customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

    }
};
