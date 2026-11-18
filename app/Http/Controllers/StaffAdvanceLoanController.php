<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ledger;
use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\StaffLoan;
use App\Models\StaffLoanInstallment;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffAdvanceLoanController extends Controller
{
    // ==================== SALARY ADVANCES ====================

    public function advancesIndex(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $query = SalaryAdvance::where('company_id', $companyId)
            ->with(['staff.department', 'ledger', 'journalEntry']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->get('staff_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $advances = $query->latest('advance_date')->paginate(15);

        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->where('status', 'active')->get();

        $totalDisbursed = SalaryAdvance::where('company_id', $companyId)->whereIn('status', ['approved', 'disbursed', 'recovered'])->sum('amount');
        $totalRecovered = SalaryAdvance::where('company_id', $companyId)->whereIn('status', ['approved', 'disbursed', 'recovered'])->sum('recovered_amount');
        $totalOutstanding = max(0, $totalDisbursed - $totalRecovered);

        $stats = [
            'total_disbursed' => $totalDisbursed,
            'total_recovered' => $totalRecovered,
            'total_outstanding' => $totalOutstanding,
            'active_advances_count' => SalaryAdvance::where('company_id', $companyId)->where('balance_amount', '>', 0)->count(),
        ];

        return view('staff.advances.index', compact('advances', 'staffMembers', 'bankLedgers', 'stats'));
    }

    public function advanceStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:Bank Transfer,Cash,Cheque,UPI',
            'ledger_id' => 'required|exists:ledgers,id',
            'reference_no' => 'nullable|string|max:100',
            'reason' => 'required|string|max:255',
        ]);

        $staff = Staff::where('company_id', $companyId)->findOrFail($data['staff_id']);
        $bankLedger = Ledger::where('company_id', $companyId)->findOrFail($data['ledger_id']);
        $amount = (float)$data['amount'];

        return DB::transaction(function () use ($companyId, $data, $staff, $bankLedger, $amount) {
            $advCount = SalaryAdvance::where('company_id', $companyId)->count() + 1;
            $advNo = 'ADV-' . date('Y') . '-' . sprintf('%04d', $advCount);

            $accountingService = new AccountingService();
            // Create or get Staff Advance Asset Ledger (Current Assets)
            $advanceAssetLedger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                'Salary Advances to Staff',
                'Loans & Advances (Asset)',
                'Assets'
            );

            // Double Entry: Dr Salary Advance Asset, Cr Bank/Cash Ledger
            $jv = $accountingService->createJournalEntry(
                $companyId,
                $staff->branch_id,
                'Payment',
                $data['advance_date'],
                "Salary Advance {$advNo} disbursed to {$staff->full_name}: {$data['reason']}",
                [
                    ['ledger_id' => $advanceAssetLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => "Advance to {$staff->full_name} ({$staff->employee_id})"],
                    ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => "Disbursement via {$data['payment_mode']}"],
                ],
                'SalaryAdvance',
                null,
                $advNo
            );

            $advance = SalaryAdvance::create([
                'company_id' => $companyId,
                'staff_id' => $staff->id,
                'advance_no' => $advNo,
                'advance_date' => $data['advance_date'],
                'amount' => $amount,
                'payment_mode' => $data['payment_mode'],
                'ledger_id' => $bankLedger->id,
                'reference_no' => $data['reference_no'] ?? null,
                'reason' => $data['reason'],
                'status' => 'approved',
                'recovered_amount' => 0,
                'balance_amount' => $amount,
                'journal_entry_id' => $jv->id,
                'approved_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $jv->update(['reference_id' => $advance->id]);

            AuditLog::log('payroll', 'create_advance', "Disbursed Salary Advance {$advNo} of ₹{$amount} to {$staff->full_name}");
            return back()->with('success', "Salary Advance {$advNo} of ₹" . number_format($amount, 2) . " disbursed and journal entry posted.");
        });
    }

    // ==================== STAFF LOANS ====================

    public function loansIndex(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $query = StaffLoan::where('company_id', $companyId)
            ->with(['staff.department', 'disbursedLedger', 'installments', 'journalEntry']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->get('staff_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $loans = $query->latest('loan_date')->paginate(15);

        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->where('status', 'active')->get();

        $totalDisbursed = StaffLoan::where('company_id', $companyId)->whereIn('status', ['approved', 'active', 'closed'])->sum('principal_amount');
        $totalOutstanding = StaffLoan::where('company_id', $companyId)->whereIn('status', ['approved', 'active'])->sum('outstanding_balance');
        $totalRepaid = max(0, $totalDisbursed - $totalOutstanding);

        $stats = [
            'total_disbursed' => $totalDisbursed,
            'total_repaid' => $totalRepaid,
            'total_outstanding' => $totalOutstanding,
            'active_loans_count' => StaffLoan::where('company_id', $companyId)->where('status', 'active')->count(),
        ];

        return view('staff.loans.index', compact('loans', 'staffMembers', 'bankLedgers', 'stats'));
    }

    public function loanStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'loan_date' => 'required|date',
            'principal_amount' => 'required|numeric|min:100',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'repayment_months' => 'required|integer|min:1|max:120',
            'start_month' => 'required|date_format:Y-m',
            'disbursed_ledger_id' => 'required|exists:ledgers,id',
        ]);

        $staff = Staff::where('company_id', $companyId)->findOrFail($data['staff_id']);
        $bankLedger = Ledger::where('company_id', $companyId)->findOrFail($data['disbursed_ledger_id']);
        $principal = (float)$data['principal_amount'];
        $interestRate = (float)($data['interest_rate'] ?? 0);
        $months = (int)$data['repayment_months'];

        return DB::transaction(function () use ($companyId, $data, $staff, $bankLedger, $principal, $interestRate, $months) {
            $loanCount = StaffLoan::where('company_id', $companyId)->count() + 1;
            $loanNo = 'LN-' . date('Y') . '-' . sprintf('%04d', $loanCount);

            // Compute EMI (Simple interest or flat principal division)
            $totalInterest = $interestRate > 0 ? ($principal * ($interestRate / 100) * ($months / 12)) : 0;
            $totalPayable = $principal + $totalInterest;
            $monthlyEmi = round($totalPayable / $months, 2);

            $accountingService = new AccountingService();
            $loanAssetLedger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                'Staff Loan Receivables',
                'Loans & Advances (Asset)',
                'Assets'
            );

            // Double-entry: Dr Staff Loan Receivable, Cr Bank/Cash Ledger
            $jv = $accountingService->createJournalEntry(
                $companyId,
                $staff->branch_id,
                'Payment',
                $data['loan_date'],
                "Staff Loan {$loanNo} sanctioned to {$staff->full_name} for {$months} months",
                [
                    ['ledger_id' => $loanAssetLedger->id, 'debit' => $principal, 'credit' => 0, 'narration' => "Loan to {$staff->full_name} ({$staff->employee_id})"],
                    ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $principal, 'narration' => "Disbursed from {$bankLedger->name}"],
                ],
                'StaffLoan',
                null,
                $loanNo
            );

            $loan = StaffLoan::create([
                'company_id' => $companyId,
                'staff_id' => $staff->id,
                'loan_no' => $loanNo,
                'loan_date' => $data['loan_date'],
                'principal_amount' => $principal,
                'interest_rate' => $interestRate,
                'repayment_months' => $months,
                'monthly_emi' => $monthlyEmi,
                'start_month' => $data['start_month'],
                'disbursed_ledger_id' => $bankLedger->id,
                'outstanding_balance' => $totalPayable,
                'status' => 'active',
                'journal_entry_id' => $jv->id,
                'approved_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $jv->update(['reference_id' => $loan->id]);

            // Generate installment schedule
            $carbonStart = Carbon::createFromFormat('Y-m', $data['start_month']);
            $accumulated = 0;

            for ($i = 1; $i <= $months; $i++) {
                $installmentAmount = ($i === $months) ? ($totalPayable - $accumulated) : $monthlyEmi;
                $accumulated += $installmentAmount;

                StaffLoanInstallment::create([
                    'staff_loan_id' => $loan->id,
                    'installment_number' => $i,
                    'due_month' => $carbonStart->copy()->addMonths($i - 1)->format('Y-m'),
                    'amount' => $installmentAmount,
                    'status' => 'pending',
                ]);
            }

            AuditLog::log('payroll', 'create_loan', "Sanctioned Staff Loan {$loanNo} of ₹{$principal} to {$staff->full_name} with {$months} EMIs of ₹{$monthlyEmi}");
            return back()->with('success', "Staff Loan {$loanNo} sanctioned and {$months} installments schedule generated.");
        });
    }

    public function loanShow(StaffLoan $loan)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($loan->company_id != $companyId) {
            abort(403);
        }

        $loan->load(['staff.department', 'disbursedLedger', 'installments.payroll', 'journalEntry.lines.ledger']);
        return view('staff.loans.show', compact('loan'));
    }
}

