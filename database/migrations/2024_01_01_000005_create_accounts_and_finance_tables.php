<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Financial Years
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title', 50); // e.g. FY 2026-2027
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'title']);
        });

        // Account Groups (Chart of Accounts Categories)
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->enum('nature', ['Assets', 'Liabilities', 'Equity', 'Revenue', 'Expenses']);
            $table->foreignId('parent_id')->nullable()->constrained('account_groups')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Ledgers
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['debit', 'credit'])->default('debit');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_bank_or_cash')->default(false);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_ifsc', 30)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Journal Entries (Vouchers Header)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voucher_no', 50);
            $table->enum('voucher_type', [
                'Journal', 'Payment', 'Receipt', 'Contra', 'Purchase', 'Sales', 
                'Purchase Return', 'Sales Return', 'Payroll', 'Expense', 'Income'
            ])->default('Journal');
            $table->date('voucher_date');
            $table->string('reference_type', 50)->nullable(); // PurchaseEntry, SalesInvoice, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('narration')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->boolean('is_posted')->default(true);
            $table->boolean('is_reversed')->default(false);
            $table->unsignedBigInteger('reversed_by_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_no']);
        });

        // Journal Lines (Vouchers Detail)
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained()->cascadeOnDelete();
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('narration')->nullable();
            $table->timestamps();
        });

        // Payment Allocations
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('payment_type', 50); // customer_collection, supplier_payment
            $table->unsignedBigInteger('voucher_id');
            $table->string('invoice_type', 50); // sales_invoice, purchase_entry
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('account_groups');
        Schema::dropIfExists('financial_years');
    }
};

