<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Attendance & Loss of Pay Columns on Payrolls Table
        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (!Schema::hasColumn('payrolls', 'total_days')) {
                    $table->smallInteger('total_days')->default(30)->after('salary_month');
                }
                if (!Schema::hasColumn('payrolls', 'present_days')) {
                    $table->decimal('present_days', 5, 1)->default(30.0)->after('total_days');
                }
                if (!Schema::hasColumn('payrolls', 'absent_days')) {
                    $table->decimal('absent_days', 5, 1)->default(0.0)->after('present_days');
                }
                if (!Schema::hasColumn('payrolls', 'lop_deduction')) {
                    $table->decimal('lop_deduction', 15, 2)->default(0.00)->after('other_deductions');
                }
            });
        }

        // 2. Petty Cash Accounts (Float Setup & Custodian)
        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('custodian_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->decimal('float_limit', 15, 2)->default(10000.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->foreignId('ledger_id')->nullable()->constrained('ledgers')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // 3. Petty Cash Expense Vouchers
        Schema::create('petty_cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->cascadeOnDelete();
            $table->string('voucher_no', 50);
            $table->date('voucher_date');
            $table->enum('expense_category', [
                'Tea & Refreshments',
                'Office Stationery',
                'Postage & Courier',
                'Local Conveyance',
                'Repairs & Maintenance',
                'Cleaning & Sanitation',
                'Staff Welfare',
                'Miscellaneous'
            ])->default('Miscellaneous');
            $table->decimal('amount', 15, 2);
            $table->string('paid_to');
            $table->string('receipt_doc')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_no']);
        });

        // 4. Petty Cash Replenishments
        Schema::create('petty_cash_replenishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->cascadeOnDelete();
            $table->string('replenishment_no', 50);
            $table->date('replenishment_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->string('transaction_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['requested', 'completed', 'cancelled'])->default('completed');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'replenishment_no']);
        });

        // 5. Investors & Partners Master
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('investor_type', ['Partner', 'Director', 'Shareholder', 'Promoter', 'Angel'])->default('Partner');
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('pan_number', 50)->nullable();
            $table->string('aadhaar_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->decimal('shareholding_percentage', 5, 2)->default(0.00);
            $table->decimal('total_capital_contributed', 15, 2)->default(0.00);
            $table->decimal('total_drawings', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->foreignId('ledger_id')->nullable()->constrained('ledgers')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // 6. Capital Investment & Drawings Transactions
        Schema::create('capital_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->string('investment_no', 50);
            $table->date('transaction_date');
            $table->enum('transaction_type', [
                'Capital Contribution',
                'Share Capital',
                'Partner Loan',
                'Capital Withdrawal',
                'Partner Drawings',
                'Dividend Payout'
            ])->default('Capital Contribution');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_mode', ['Bank Transfer', 'Cheque', 'Cash', 'Asset Transfer'])->default('Bank Transfer');
            $table->foreignId('deposit_ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'investment_no']);
        });

        // 7. Bank Reconciliation Headers
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->string('reconciliation_no', 50);
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2)->default(0.00);
            $table->decimal('book_balance', 15, 2)->default(0.00);
            $table->decimal('reconciled_balance', 15, 2)->default(0.00);
            $table->decimal('difference', 15, 2)->default(0.00);
            $table->enum('status', ['in_progress', 'reconciled'])->default('in_progress');
            $table->text('notes')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'reconciliation_no']);
        });

        // 8. Bank Reconciliation Lines
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->date('transaction_date');
            $table->date('clearance_date')->nullable();
            $table->string('cheque_reference', 100)->nullable();
            $table->string('description');
            $table->decimal('debit_amount', 15, 2)->default(0.00);
            $table->decimal('credit_amount', 15, 2)->default(0.00);
            $table->boolean('is_cleared')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('capital_investments');
        Schema::dropIfExists('investors');
        Schema::dropIfExists('petty_cash_replenishments');
        Schema::dropIfExists('petty_cash_vouchers');
        Schema::dropIfExists('petty_cash_accounts');

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $columns = ['total_days', 'present_days', 'absent_days', 'lop_deduction'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('payrolls', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
