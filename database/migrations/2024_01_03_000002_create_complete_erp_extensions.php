<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unit Conversion Columns for Line Items
        if (Schema::hasTable('purchase_entry_items')) {
            Schema::table('purchase_entry_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_entry_items', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('item_id')->constrained('units')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_entry_items', 'entered_quantity')) {
                    $table->decimal('entered_quantity', 15, 3)->nullable()->after('unit_id');
                }
                if (!Schema::hasColumn('purchase_entry_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 15, 3)->nullable()->after('entered_quantity');
                }
            });
        }

        if (Schema::hasTable('sales_invoice_items')) {
            Schema::table('sales_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_invoice_items', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('item_id')->constrained('units')->nullOnDelete();
                }
                if (!Schema::hasColumn('sales_invoice_items', 'entered_quantity')) {
                    $table->decimal('entered_quantity', 15, 3)->nullable()->after('unit_id');
                }
                if (!Schema::hasColumn('sales_invoice_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 15, 3)->nullable()->after('entered_quantity');
                }
            });
        }

        if (Schema::hasTable('purchase_return_items')) {
            Schema::table('purchase_return_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_return_items', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('item_id')->constrained('units')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_return_items', 'entered_quantity')) {
                    $table->decimal('entered_quantity', 15, 3)->nullable()->after('unit_id');
                }
                if (!Schema::hasColumn('purchase_return_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 15, 3)->nullable()->after('entered_quantity');
                }
            });
        }

        if (Schema::hasTable('sales_return_items')) {
            Schema::table('sales_return_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_return_items', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('item_id')->constrained('units')->nullOnDelete();
                }
                if (!Schema::hasColumn('sales_return_items', 'entered_quantity')) {
                    $table->decimal('entered_quantity', 15, 3)->nullable()->after('unit_id');
                }
                if (!Schema::hasColumn('sales_return_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 15, 3)->nullable()->after('entered_quantity');
                }
            });
        }

        // 2. Additional Charges on Purchase Entries
        if (Schema::hasTable('purchase_entries')) {
            Schema::table('purchase_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_entries', 'freight_charges')) {
                    $table->decimal('freight_charges', 15, 2)->default(0.00)->after('discount_amount');
                }
                if (!Schema::hasColumn('purchase_entries', 'packaging_charges')) {
                    $table->decimal('packaging_charges', 15, 2)->default(0.00)->after('freight_charges');
                }
                if (!Schema::hasColumn('purchase_entries', 'loading_charges')) {
                    $table->decimal('loading_charges', 15, 2)->default(0.00)->after('packaging_charges');
                }
                if (!Schema::hasColumn('purchase_entries', 'other_charges')) {
                    $table->decimal('other_charges', 15, 2)->default(0.00)->after('loading_charges');
                }
                if (!Schema::hasColumn('purchase_entries', 'round_off')) {
                    $table->decimal('round_off', 8, 2)->default(0.00)->after('other_charges');
                }
                if (!Schema::hasColumn('purchase_entries', 'charge_allocation_method')) {
                    $table->string('charge_allocation_method', 20)->default('value')->after('round_off');
                }
            });
        }

        // 3. Bank Reconciliation Import & Matching Extensions
        if (Schema::hasTable('bank_reconciliations')) {
            Schema::table('bank_reconciliations', function (Blueprint $table) {
                if (!Schema::hasColumn('bank_reconciliations', 'import_filename')) {
                    $table->string('import_filename')->nullable()->after('notes');
                }
            });
        }

        if (Schema::hasTable('bank_reconciliation_items')) {
            Schema::table('bank_reconciliation_items', function (Blueprint $table) {
                if (!Schema::hasColumn('bank_reconciliation_items', 'source')) {
                    $table->enum('source', ['system', 'imported'])->default('system')->after('description');
                }
                if (!Schema::hasColumn('bank_reconciliation_items', 'matched_item_id')) {
                    $table->unsignedBigInteger('matched_item_id')->nullable()->after('is_cleared');
                }
            });
        }

        // 4. Petty Cash Categories Master
        if (!Schema::hasTable('petty_cash_categories')) {
            Schema::create('petty_cash_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->foreignId('ledger_id')->nullable()->constrained('ledgers')->nullOnDelete();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->unique(['company_id', 'name']);
            });
        }

        if (Schema::hasTable('petty_cash_vouchers')) {
            Schema::table('petty_cash_vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('petty_cash_vouchers', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('expense_category')->constrained('petty_cash_categories')->nullOnDelete();
                }
                // Allow custom category strings
                $table->string('expense_category', 100)->change();
            });
        }

        // 5. Staff Advance & Loan Management
        if (!Schema::hasTable('salary_advances')) {
            Schema::create('salary_advances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('advance_no', 50);
                $table->date('advance_date');
                $table->decimal('amount', 15, 2);
                $table->enum('payment_mode', ['Cash', 'Bank Transfer', 'Cheque', 'UPI'])->default('Bank Transfer');
                $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
                $table->string('reference_no', 100)->nullable();
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'approved', 'disbursed', 'recovered', 'cancelled'])->default('disbursed');
                $table->decimal('recovered_amount', 15, 2)->default(0.00);
                $table->decimal('balance_amount', 15, 2)->default(0.00);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'advance_no']);
            });
        }

        if (!Schema::hasTable('staff_loans')) {
            Schema::create('staff_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('loan_no', 50);
                $table->date('loan_date');
                $table->decimal('principal_amount', 15, 2);
                $table->decimal('interest_rate', 5, 2)->default(0.00);
                $table->integer('repayment_months')->default(12);
                $table->decimal('monthly_emi', 15, 2);
                $table->string('start_month', 7); // YYYY-MM
                $table->foreignId('disbursed_ledger_id')->constrained('ledgers')->cascadeOnDelete();
                $table->decimal('outstanding_balance', 15, 2);
                $table->enum('status', ['pending', 'approved', 'disbursed', 'active', 'closed', 'cancelled'])->default('active');
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'loan_no']);
            });
        }

        if (!Schema::hasTable('staff_loan_installments')) {
            Schema::create('staff_loan_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_loan_id')->constrained('staff_loans')->cascadeOnDelete();
                $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
                $table->integer('installment_number');
                $table->string('due_month', 7); // YYYY-MM
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'deducted', 'skipped'])->default('pending');
                $table->timestamp('deducted_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (!Schema::hasColumn('payrolls', 'advance_deduction')) {
                    $table->decimal('advance_deduction', 15, 2)->default(0.00)->after('lop_deduction');
                }
                if (!Schema::hasColumn('payrolls', 'loan_deduction')) {
                    $table->decimal('loan_deduction', 15, 2)->default(0.00)->after('advance_deduction');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (Schema::hasColumn('payrolls', 'advance_deduction')) {
                    $table->dropColumn('advance_deduction');
                }
                if (Schema::hasColumn('payrolls', 'loan_deduction')) {
                    $table->dropColumn('loan_deduction');
                }
            });
        }

        Schema::dropIfExists('staff_loan_installments');
        Schema::dropIfExists('staff_loans');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('petty_cash_categories');
    }
};
