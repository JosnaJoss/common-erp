<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ledger;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Staff;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $salaryMonth = $request->get('month', Carbon::now()->format('Y-m'));

        $payrolls = Payroll::where('company_id', $companyId)
            ->where('salary_month', $salaryMonth)
            ->with(['staff.department', 'journalEntry'])
            ->get();

        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->get();

        return view('staff.payroll.index', compact('payrolls', 'salaryMonth', 'bankLedgers'));
    }

    public function generate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $salaryMonth = $request->validate(['salary_month' => 'required|date_format:Y-m'])['salary_month'];

        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $generatedCount = 0;

        foreach ($staffMembers as $staff) {
            // Check overtime for this month
            $otAmount = Overtime::where('company_id', $companyId)
                ->where('staff_id', $staff->id)
                ->where('approval_status', 'approved')
                ->where('date', 'like', "{$salaryMonth}%")
                ->sum('total_amount');

            $basic = (float)$staff->basic_salary;
            $hra = (float)$staff->hra;
            $otherAllowances = (float)$staff->other_allowances;

            $pfDeduction = $staff->pf_applicable ? ($basic * ((float)$staff->pf_rate_percent / 100)) : 0;
            $esiDeduction = $staff->esi_applicable ? (((float)$staff->salary_amount) * ((float)$staff->esi_rate_percent / 100)) : 0;

            $gross = $basic + $hra + $otherAllowances + (float)$otAmount;
            $totalDeductions = $pfDeduction + $esiDeduction;
            $net = $gross - $totalDeductions;

            Payroll::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'staff_id' => $staff->id,
                    'salary_month' => $salaryMonth,
                ],
                [
                    'branch_id' => $staff->branch_id,
                    'department_id' => $staff->department_id,
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'other_allowances' => $otherAllowances,
                    'incentives' => 0,
                    'overtime_amount' => $otAmount,
                    'pf_deduction' => $pfDeduction,
                    'esi_deduction' => $esiDeduction,
                    'other_deductions' => 0,
                    'gross_salary' => $gross,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $net,
                    'payroll_status' => 'generated',
                    'processed_by' => auth()->id(),
                ]
            );
            $generatedCount++;
        }

        AuditLog::log('payroll', 'generate', "Generated payroll for {$generatedCount} staff for {$salaryMonth}");
        return back()->with('success', "Payroll generated for {$generatedCount} staff members for {$salaryMonth}.");
    }

    public function payslip(Payroll $payroll)
    {
        $payroll->load(['staff.department', 'company']);
        return view('staff.payroll.payslip', compact('payroll'));
    }

    public function pay(Request $request, Payroll $payroll)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque',
            'payment_ledger_id' => 'required|exists:ledgers,id',
            'payment_date' => 'required|date',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $accountingService = new AccountingService();
        $salaryExpLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Salaries & Staff Wages', 'Administrative & Indirect Expenses', 'Expenses');
        $pfPayableLedger = $accountingService->getOrCreateStandardLedger($companyId, 'PF & ESI Payable', 'Duties & Taxes', 'Liabilities');
        $bankLedger = Ledger::findOrFail($data['payment_ledger_id']);

        $lines = [
            ['ledger_id' => $salaryExpLedger->id, 'debit' => $payroll->gross_salary, 'credit' => 0, 'narration' => "Salary Expense for {$payroll->staff->full_name}"],
        ];

        if ($payroll->total_deductions > 0) {
            $lines[] = ['ledger_id' => $pfPayableLedger->id, 'debit' => 0, 'credit' => $payroll->total_deductions, 'narration' => 'PF & ESI Payable'];
        }

        $lines[] = ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $payroll->net_salary, 'narration' => "Disbursement to {$payroll->staff->full_name} via {$data['payment_mode']}"];

        $jv = $accountingService->createJournalEntry(
            $companyId, $payroll->branch_id, 'Payroll', $data['payment_date'],
            "Salary disbursement for {$payroll->staff->full_name} for {$payroll->salary_month}",
            $lines,
            'Payroll', $payroll->id, "PAYR-{$payroll->staff->employee_id}-{$payroll->salary_month}"
        );

        $payroll->update([
            'payroll_status' => 'paid',
            'payment_date' => $data['payment_date'],
            'payment_mode' => $data['payment_mode'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'journal_entry_id' => $jv->id,
        ]);

        AuditLog::log('payroll', 'pay', "Disbursed salary for {$payroll->staff->full_name} (JV: {$jv->voucher_no})");
        return back()->with('success', "Salary marked as paid and journal entry {$jv->voucher_no} posted.");
    }
}

