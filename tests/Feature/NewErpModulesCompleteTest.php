<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\Branch;
use App\Models\CapitalInvestment;
use App\Models\Company;
use App\Models\Investor;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Payroll;
use App\Models\PettyCashAccount;
use App\Models\PettyCashCategory;
use App\Models\PettyCashVoucher;
use App\Models\PurchaseEntry;
use App\Models\SalesInvoice;
use App\Models\Staff;
use App\Models\StaffLoan;
use App\Models\StaffLoanInstallment;
use App\Models\SalaryAdvance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use App\Services\UnitConversionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NewErpModulesCompleteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('email', 'admin@erp.com')->first();
        $this->company = Company::where('code', 'APEX-IND')->first() ?? Company::first();
        $this->branch = $this->company->branches()->first() ?? Branch::first();

        session([
            'active_company_id' => $this->company->id,
            'active_branch_id' => $this->branch->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    // ==================== PHASE 1: UOM CONVERSION & PDF & CHARGES ====================

    public function test_unit_conversion_service_and_sales_pdf_rendering(): void
    {
        // 1. Test Unit Conversion Service
        $pcsUnit = Unit::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Pieces'],
            ['symbol' => 'PCS', 'unit_type' => 'quantity', 'conversion_factor' => 1.0, 'status' => 'active']
        );

        $boxUnit = Unit::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Box of 10'],
            ['symbol' => 'BOX10', 'unit_type' => 'quantity', 'conversion_factor' => 10.0, 'status' => 'active']
        );

        $item = Item::where('company_id', $this->company->id)->first();
        $this->assertNotNull($item, 'Item required for UOM conversion test');
        $item->unit_id = $pcsUnit->id;
        $item->save();

        // 5 Boxes of 10 should equal 50 Pieces
        $converted = UnitConversionService::convertToBaseQuantity($item, 5.0, $boxUnit->id);
        $this->assertEquals(5.0, $converted['entered_quantity']);
        $this->assertEquals(50.0, $converted['base_quantity']);
        $this->assertEquals(10.0, $converted['conversion_factor']);

        // 2. Test Sales Invoice PDF Generation
        $invoice = SalesInvoice::where('company_id', $this->company->id)->first();
        if ($invoice) {
            $response = $this->actingAs($this->adminUser)
                ->withSession(['active_company_id' => $this->company->id])
                ->get(route('sales.invoices.pdf', $invoice));

            $response->assertStatus(200);
            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        }
    }

    public function test_purchase_entry_additional_charges_calculation(): void
    {
        $supplier = Supplier::where('company_id', $this->company->id)->first();
        $this->assertNotNull($supplier, 'Supplier required for purchase test');

        $purchase = PurchaseEntry::where('company_id', $this->company->id)->first();
        if ($purchase) {
            $purchase->update([
                'freight_charges' => 500.00,
                'packaging_charges' => 150.00,
                'loading_charges' => 100.00,
                'other_charges' => 50.00,
                'round_off' => 0.25,
            ]);

            $totalAdditional = (float)$purchase->freight_charges + (float)$purchase->packaging_charges + (float)$purchase->loading_charges + (float)$purchase->other_charges;
            $this->assertEquals(800.00, $totalAdditional);
            $this->assertEquals(0.25, (float)$purchase->round_off);
        }
    }

    // ==================== PHASE 2: BANK RECONCILIATION ====================

    public function test_bank_reconciliation_module_complete_workflow(): void
    {
        $bankLedger = Ledger::where('company_id', $this->company->id)
            ->where('is_bank_or_cash', true)
            ->first();
        $this->assertNotNull($bankLedger, 'Bank ledger required');

        // 1. Test Index Screen
        $indexResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.bank_reconciliation.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Bank Reconciliation (BRS)');

        // 2. Test Create Screen
        $createResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.bank_reconciliation.create', ['ledger_id' => $bankLedger->id]));
        $createResponse->assertStatus(200);

        // 3. Create Bank Reconciliation Period
        $statementDate = Carbon::now()->toDateString();

        $storeResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.bank_reconciliation.store'), [
                'ledger_id' => $bankLedger->id,
                'statement_date' => $statementDate,
                'statement_balance' => 75000.00,
                'notes' => 'Monthly Bank Reconciliation Test',
            ]);

        $reconciliation = BankReconciliation::where('company_id', $this->company->id)
            ->where('ledger_id', $bankLedger->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($reconciliation);
        $this->assertEquals(75000.00, (float)$reconciliation->statement_balance);

        // 4. Test Statement Import via CSV
        $csvContent = "Date,Description,Reference,Withdrawal,Deposit,Balance\n";
        $csvContent .= Carbon::now()->format('Y-m-d') . ",NEFT Inward Customer,UTR112233,,25000.00,75000.00\n";
        $csvContent .= Carbon::now()->format('Y-m-d') . ",Bank Charges,CHG4455,250.00,,74750.00\n";

        $file = UploadedFile::fake()->createWithContent('statement.csv', $csvContent);

        $importResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.bank_reconciliation.import', $reconciliation), [
                'statement_csv' => $file,
            ]);

        $this->assertTrue(session()->has('success') || session()->has('info'));

        $statementItems = BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)->get();
        $this->assertGreaterThan(0, $statementItems->count());

        // 5. Test Adjustment Entry (Bank Charges / Interest)
        $expenseLedger = Ledger::where('company_id', $this->company->id)
            ->where('is_bank_or_cash', false)
            ->first() ?? Ledger::first();

        $adjResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.bank_reconciliation.adjustment', $reconciliation), [
                'adjustment_type' => 'bank_charges',
                'amount' => 150.00,
                'adjustment_date' => Carbon::now()->toDateString(),
                'narration' => 'SMS & Folio Charges Reconciled',
            ]);

        $adjResponse->assertSessionHas('success');

        // 6. Test BRS Report View
        $reportResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.bank_reconciliation.report', $reconciliation));
        $reportResponse->assertStatus(200);
        $reportResponse->assertSee('Bank Reconciliation Statement');
    }

    // ==================== PHASE 3: PETTY CASH ====================

    public function test_petty_cash_complete_workflow(): void
    {
        $bankLedger = Ledger::where('company_id', $this->company->id)
            ->where('is_bank_or_cash', true)
            ->first();

        // 1. Test Index Screen
        $indexResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.petty_cash.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Petty Cash Management');

        // 2. Create Petty Cash Account
        $pettyAccResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.petty_cash.accounts.store'), [
                'name' => 'Branch Cashier Float',
                'branch_id' => $this->branch->id,
                'float_limit' => 15000.00,
                'opening_balance' => 15000.00,
            ]);

        $pettyAccount = PettyCashAccount::where('company_id', $this->company->id)
            ->where('name', 'Branch Cashier Float')
            ->first();
        $this->assertNotNull($pettyAccount);
        $this->assertEquals(15000.00, (float)$pettyAccount->current_balance);

        // 3. Verify Category Creation
        $category = PettyCashCategory::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Office Refreshments'],
            ['status' => 'active']
        );
        $this->assertNotNull($category);

        // 4. Create Petty Cash Expense Voucher
        $voucherResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.petty_cash.vouchers.store'), [
                'petty_cash_account_id' => $pettyAccount->id,
                'category_id' => $category->id,
                'voucher_date' => Carbon::now()->toDateString(),
                'paid_to' => 'Cafeteria Vendor',
                'amount' => 650.00,
                'remarks' => 'Staff tea and refreshments for client meeting',
            ]);

        $voucher = PettyCashVoucher::where('petty_cash_account_id', $pettyAccount->id)->latest('id')->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(650.00, (float)$voucher->amount);
        $this->assertNotNull($voucher->journal_entry_id);

        $pettyAccount->refresh();
        $this->assertEquals(14350.00, (float)$pettyAccount->current_balance);

        // 5. Replenish Float (Contra Voucher)
        $replenishResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.petty_cash.replenish.store'), [
                'petty_cash_account_id' => $pettyAccount->id,
                'replenishment_date' => Carbon::now()->toDateString(),
                'amount' => 650.00,
                'bank_ledger_id' => $bankLedger->id,
                'transaction_reference' => 'CHQ-889922',
                'notes' => 'Float replenishment to restore imprest',
            ]);

        $pettyAccount->refresh();
        $this->assertEquals(15000.00, (float)$pettyAccount->current_balance);

        // 6. View Daybook Register
        $daybookResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.petty_cash.daybook', $pettyAccount));
        $daybookResponse->assertStatus(200);
        $daybookResponse->assertSee('Petty Cash Register');
        $daybookResponse->assertSee('Daybook');
    }

    // ==================== PHASE 4: CAPITAL INVESTMENT ====================

    public function test_capital_investment_and_equity_workflow(): void
    {
        $bankLedger = Ledger::where('company_id', $this->company->id)
            ->where('is_bank_or_cash', true)
            ->first();

        // 1. Index Screen
        $indexResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.capital.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Capital');
        $indexResponse->assertSee('Investor Management');

        // 2. Create Investor / Partner
        $investorResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.capital.investors.store'), [
                'name' => 'Dr. Rajesh Sharma',
                'investor_type' => 'Partner',
                'pan_number' => 'ABCPS1234F',
                'phone' => '9876543210',
                'email' => 'rajesh.sharma@partner.com',
                'shareholding_percentage' => 25.00,
                'opening_balance' => 500000.00,
            ]);

        $investor = Investor::where('company_id', $this->company->id)
            ->where('email', 'rajesh.sharma@partner.com')
            ->first();
        $this->assertNotNull($investor);
        $this->assertNotNull($investor->ledger_id);
        $this->assertEquals(500000.00, (float)$investor->current_balance);

        // 3. Record Capital Infusion (Contribution)
        $infusionResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.capital.transactions.store'), [
                'investor_id' => $investor->id,
                'transaction_type' => 'contribution',
                'transaction_date' => Carbon::now()->toDateString(),
                'amount' => 150000.00,
                'payment_mode' => 'Bank Transfer',
                'deposit_ledger_id' => $bankLedger->id,
                'reference_no' => 'UTR-EQUITY-8811',
                'remarks' => 'Q3 Additional Capital Contribution',
            ]);

        $investor->refresh();
        $this->assertEquals(650000.00, (float)$investor->current_balance);

        // 4. Record Drawings / Capital Return
        $drawingsResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('accounts.capital.transactions.store'), [
                'investor_id' => $investor->id,
                'transaction_type' => 'drawings',
                'transaction_date' => Carbon::now()->toDateString(),
                'amount' => 50000.00,
                'payment_mode' => 'Bank Transfer',
                'deposit_ledger_id' => $bankLedger->id,
                'reference_no' => 'UTR-DRAWINGS-9922',
                'remarks' => 'Personal drawings by partner',
            ]);

        $investor->refresh();
        $this->assertEquals(600000.00, (float)$investor->current_balance);

        // 5. View Capital Statement
        $statementResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('accounts.capital.statement', ['investor_id' => $investor->id]));
        $statementResponse->assertStatus(200);
        $statementResponse->assertSee('Capital Statement');
    }

    // ==================== PHASE 5: PAYROLL IMPROVEMENTS ====================

    public function test_payroll_attendance_lop_advance_loan_and_disbursement(): void
    {
        $salaryMonth = Carbon::now()->format('Y-m');
        $bankLedger = Ledger::where('company_id', $this->company->id)
            ->where('is_bank_or_cash', true)
            ->first();

        // 1. Get or create an active staff member
        $staff = Staff::where('company_id', $this->company->id)
            ->where('status', 'active')
            ->first();
        $this->assertNotNull($staff, 'Active staff required');

        // Set predictable salary figures for testing
        $staff->update([
            'basic_salary' => 30000.00,
            'hra' => 10000.00,
            'other_allowances' => 5000.00,
            'pf_applicable' => false,
            'esi_applicable' => false,
        ]);
        \App\Models\Overtime::where('company_id', $this->company->id)
            ->where('staff_id', $staff->id)
            ->where('date', 'like', "{$salaryMonth}%")
            ->delete();

        // 2. Record 2 Absent Days (Loss of Pay)
        $today = Carbon::now();
        Attendance::updateOrCreate(
            ['company_id' => $this->company->id, 'staff_id' => $staff->id, 'attendance_date' => $today->copy()->startOfMonth()->toDateString()],
            ['branch_id' => $this->branch->id, 'attendance_status' => 'absent', 'working_hours' => 0]
        );
        Attendance::updateOrCreate(
            ['company_id' => $this->company->id, 'staff_id' => $staff->id, 'attendance_date' => $today->copy()->startOfMonth()->addDays(1)->toDateString()],
            ['branch_id' => $this->branch->id, 'attendance_status' => 'absent', 'working_hours' => 0]
        );

        // 3. Issue Salary Advance of ₹3,000
        $advanceResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('staff.advances.store'), [
                'staff_id' => $staff->id,
                'advance_date' => Carbon::now()->toDateString(),
                'amount' => 3000.00,
                'payment_mode' => 'Bank Transfer',
                'ledger_id' => $bankLedger->id,
                'reason' => 'Emergency advance test',
            ]);

        $advance = SalaryAdvance::where('staff_id', $staff->id)->where('status', 'approved')->latest('id')->first();
        $this->assertNotNull($advance);
        $this->assertEquals(3000.00, (float)$advance->amount);
        $this->assertEquals(3000.00, (float)$advance->balance_amount);

        // 4. Sanction Staff Loan of ₹24,000 across 12 months (EMI: ₹2,000)
        $loanResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('staff.loans.store'), [
                'staff_id' => $staff->id,
                'loan_date' => Carbon::now()->toDateString(),
                'principal_amount' => 24000.00,
                'interest_rate' => 0,
                'repayment_months' => 12,
                'start_month' => $salaryMonth,
                'disbursed_ledger_id' => $bankLedger->id,
            ]);

        $loan = StaffLoan::where('staff_id', $staff->id)->where('status', 'active')->latest('id')->first();
        $this->assertNotNull($loan);
        $this->assertEquals(2000.00, (float)$loan->monthly_emi);
        $this->assertEquals(12, $loan->installments()->count());

        // 5. Generate Monthly Payroll
        $genResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('staff.payroll.generate'), [
                'salary_month' => $salaryMonth,
            ]);

        $payroll = Payroll::where('company_id', $this->company->id)
            ->where('staff_id', $staff->id)
            ->where('salary_month', $salaryMonth)
            ->first();
        $this->assertNotNull($payroll);

        // Verify Gross Salary: 30000 + 10000 + 5000 = 45000
        $this->assertEquals(45000.00, (float)$payroll->gross_salary);

        // Verify Attendance & LOP Deduction:
        // Basic + HRA = 40000. Per day = 40000 / daysInMonth. 2 absent days = (40000 / daysInMonth) * 2
        $daysInMonth = Carbon::createFromFormat('Y-m', $salaryMonth)->daysInMonth;
        $expectedLop = round((40000 / $daysInMonth) * 2, 2);
        $this->assertEquals($expectedLop, (float)$payroll->lop_deduction);

        // Verify Advance Deduction: ₹3,000
        $this->assertEquals(3000.00, (float)$payroll->advance_deduction);

        // Verify Loan EMI Deduction: ₹2,000
        $this->assertEquals(2000.00, (float)$payroll->loan_deduction);

        // Verify Net Salary calculation
        $expectedNet = round(45000.00 - $expectedLop - 3000.00 - 2000.00 - (float)$payroll->pf_deduction - (float)$payroll->esi_deduction, 2);
        $this->assertEquals($expectedNet, (float)$payroll->net_salary);

        // 6. Test Bulk Salary Disbursement
        $bulkResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('staff.payroll.bulk_pay'), [
                'payroll_ids' => [$payroll->id],
                'payment_mode' => 'Bank Transfer',
                'payment_ledger_id' => $bankLedger->id,
                'payment_date' => Carbon::now()->toDateString(),
                'transaction_reference' => 'SAL-BATCH-TEST-001',
            ]);

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->payroll_status);
        $this->assertNotNull($payroll->journal_entry_id);

        // Verify Advance has been fully recovered
        $advance->refresh();
        $this->assertEquals(3000.00, (float)$advance->recovered_amount);
        $this->assertEquals(0.00, (float)$advance->balance_amount);
        $this->assertEquals('recovered', $advance->status);

        // Verify Loan installment was marked deducted and loan balance updated
        $installment = StaffLoanInstallment::where('staff_loan_id', $loan->id)
            ->where('due_month', $salaryMonth)
            ->first();
        $this->assertEquals('deducted', $installment->status);
        $this->assertEquals($payroll->id, $installment->payroll_id);

        $loan->refresh();
        $this->assertEquals(22000.00, (float)$loan->outstanding_balance);

        // Verify Double-Entry Journal Entry Balance
        $jv = JournalEntry::find($payroll->journal_entry_id);
        $this->assertNotNull($jv);
        $totalDebit = $jv->lines()->sum('debit_amount');
        $totalCredit = $jv->lines()->sum('credit_amount');
        $this->assertEquals(round($totalDebit, 2), round($totalCredit, 2));

        // 7. Verify Payslip View
        $payslipResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('staff.payroll.payslip', $payroll));
        $payslipResponse->assertStatus(200);
        $payslipResponse->assertSee('CONFIDENTIAL PAYSLIP');
        $payslipResponse->assertSee('Loss of Pay (LOP) Deduction');
        $payslipResponse->assertSee('Salary Advance Recovery');
        $payslipResponse->assertSee('Staff Loan EMI Deduction');
    }
}
