<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\Ledger;
use App\Models\Overtime;
use App\Models\Payroll;
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
use Illuminate\Validation\Rule;

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

        $summary = [
            'total_gross' => $payrolls->sum('gross_salary'),
            'total_net' => $payrolls->sum('net_salary'),
            'total_lop' => $payrolls->sum('lop_deduction'),
            'total_advances' => $payrolls->sum('advance_deduction'),
            'total_loans' => $payrolls->sum('loan_deduction'),
            'pending_count' => $payrolls->where('payroll_status', 'generated')->count(),
            'paid_count' => $payrolls->where('payroll_status', 'paid')->count(),
        ];

        return view('staff.payroll.index', compact('payrolls', 'salaryMonth', 'bankLedgers', 'summary'));
    }

    public function generate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $salaryMonth = $request->validate(['salary_month' => 'required|date_format:Y-m'])['salary_month'];

        $carbonMonth = Carbon::createFromFormat('Y-m', $salaryMonth);
        $totalDaysInMonth = $carbonMonth->daysInMonth;
        $monthStart = $carbonMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $carbonMonth->copy()->endOfMonth()->toDateString();

        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $generatedCount = 0;

        foreach ($staffMembers as $staff) {
            // 1. Attendance and Leave Integration
            $attendances = Attendance::where('company_id', $companyId)
                ->where('staff_id', $staff->id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                ->get();

            $recordedAbsent = 0;
            $halfDays = 0;
            $presentCount = 0;

            foreach ($attendances as $att) {
                $st = strtolower(str_replace([' ', '_', '-'], '', $att->attendance_status));
                if ($st === 'absent') {
                    $recordedAbsent++;
                } elseif ($st === 'halfday') {
                    $halfDays++;
                } elseif (in_array($st, ['present', 'weeklyoff', 'holiday'])) {
                    $presentCount++;
                }
            }

            // Approved Leaves in this month
            $approvedLeaves = LeaveApplication::where('company_id', $companyId)
                ->where('staff_id', $staff->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $monthEnd)
                ->where('end_date', '>=', $monthStart)
                ->get();

            $unpaidLeaveDays = 0;
            $paidLeaveDays = 0;

            foreach ($approvedLeaves as $lv) {
                $lStart = Carbon::parse(max($lv->start_date, $monthStart));
                $lEnd = Carbon::parse(min($lv->end_date, $monthEnd));
                $days = $lStart->diffInDays($lEnd) + 1;

                if (strtolower($lv->leave_type) === 'unpaid') {
                    $unpaidLeaveDays += $days;
                } else {
                    $paidLeaveDays += $days;
                }
            }

            // Loss of Pay (LOP) Days calculation
            if ($attendances->isNotEmpty()) {
                $lopDays = (float)$recordedAbsent + ((float)$halfDays * 0.5) + (float)$unpaidLeaveDays;
                $presentDays = max(0, $totalDaysInMonth - $lopDays);
            } else {
                $lopDays = (float)$unpaidLeaveDays;
                $presentDays = max(0, $totalDaysInMonth - $lopDays);
            }

            $basic = (float)$staff->basic_salary;
            $hra = (float)$staff->hra;
            $otherAllowances = (float)$staff->other_allowances;

            // LOP deduction formula based on basic + hra
            $lopDeduction = $lopDays > 0 ? round((($basic + $hra) / $totalDaysInMonth) * $lopDays, 2) : 0;

            // Overtime amount for this month
            $otAmount = (float)Overtime::where('company_id', $companyId)
                ->where('staff_id', $staff->id)
                ->where('approval_status', 'approved')
                ->where('date', 'like', "{$salaryMonth}%")
                ->sum('total_amount');

            // Statutory deductions (PF & ESI)
            $pfDeduction = $staff->pf_applicable ? ($basic * ((float)$staff->pf_rate_percent / 100)) : 0;
            $esiDeduction = $staff->esi_applicable ? (((float)$staff->salary_amount) * ((float)$staff->esi_rate_percent / 100)) : 0;

            // 2. Staff Salary Advance Recovery
            $activeAdvances = SalaryAdvance::where('company_id', $companyId)
                ->where('staff_id', $staff->id)
                ->whereIn('status', ['approved', 'disbursed'])
                ->where('balance_amount', '>', 0)
                ->get();

            $advanceDeduction = 0;
            $availableForDeductions = max(0, ($basic + $hra + $otherAllowances + $otAmount) - $pfDeduction - $esiDeduction - $lopDeduction);

            foreach ($activeAdvances as $adv) {
                $rem = (float)$adv->balance_amount;
                if ($rem > 0 && $availableForDeductions > 0) {
                    $deduct = min($rem, $availableForDeductions);
                    $advanceDeduction += $deduct;
                    $availableForDeductions -= $deduct;
                }
            }

            // 3. Staff Loan Installment EMI
            $dueInstallments = StaffLoanInstallment::whereHas('loan', function ($q) use ($companyId, $staff) {
                    $q->where('company_id', $companyId)
                        ->where('staff_id', $staff->id)
                        ->where('status', 'active');
                })
                ->where('due_month', $salaryMonth)
                ->where('status', 'pending')
                ->get();

            $loanDeduction = (float)$dueInstallments->sum('amount');

            // 4. Gross and Net Salary
            $gross = $basic + $hra + $otherAllowances + $otAmount;
            $totalDeductions = $pfDeduction + $esiDeduction + $lopDeduction + $advanceDeduction + $loanDeduction;
            $net = max(0, $gross - $totalDeductions);

            Payroll::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'staff_id' => $staff->id,
                    'salary_month' => $salaryMonth,
                ],
                [
                    'branch_id' => $staff->branch_id,
                    'department_id' => $staff->department_id,
                    'total_days' => $totalDaysInMonth,
                    'present_days' => $presentDays,
                    'absent_days' => $lopDays,
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'other_allowances' => $otherAllowances,
                    'incentives' => 0,
                    'overtime_amount' => $otAmount,
                    'pf_deduction' => $pfDeduction,
                    'esi_deduction' => $esiDeduction,
                    'other_deductions' => 0,
                    'lop_deduction' => $lopDeduction,
                    'advance_deduction' => $advanceDeduction,
                    'loan_deduction' => $loanDeduction,
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
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($payroll->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company payslip.');
        }

        $payroll->load(['staff.department', 'company', 'journalEntry']);
        return view('staff.payroll.payslip', compact('payroll'));
    }

    public function pay(Request $request, Payroll $payroll)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($payroll->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque',
            'payment_ledger_id' => [
                'required',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'payment_date' => 'required|date',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['payment_date'])
            ->where('end_date', '>=', $data['payment_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->with('error', 'Cannot disburse salary in a closed financial year period.');
        }

        DB::transaction(function () use ($companyId, $data, $payroll) {
            $this->disburseSinglePayroll($payroll, $data, $companyId);
        });

        AuditLog::log('payroll', 'pay', "Disbursed salary for {$payroll->staff->full_name}");
        return back()->with('success', "Salary marked as paid and journal entry posted.");
    }

    public function bulkPay(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => [
                Rule::exists('payrolls', 'id')->where('company_id', $companyId),
            ],
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque',
            'payment_ledger_id' => [
                'required',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'payment_date' => 'required|date',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $payrolls = Payroll::where('company_id', $companyId)
            ->whereIn('id', $data['payroll_ids'])
            ->where('payroll_status', 'generated')
            ->with(['staff'])
            ->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'No eligible generated payroll records selected for disbursement.');
        }

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['payment_date'])
            ->where('end_date', '>=', $data['payment_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->with('error', 'Cannot disburse salary in a closed financial year period.');
        }

        $disbursedCount = 0;
        DB::transaction(function () use ($companyId, $data, $payrolls, &$disbursedCount) {
            foreach ($payrolls as $payroll) {
                $this->disburseSinglePayroll($payroll, $data, $companyId);
                $disbursedCount++;
            }
        });

        AuditLog::log('payroll', 'bulk_pay', "Disbursed {$disbursedCount} staff salaries in bulk via {$data['payment_mode']}");
        return back()->with('success', "Batch disbursement completed successfully for {$disbursedCount} staff members.");
    }

    // ==================== INTERNAL HELPER METHOD ====================

    private function disburseSinglePayroll(Payroll $payroll, array $data, int $companyId): void
    {
        $accountingService = new AccountingService();
        $salaryExpLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Salaries & Staff Wages', 'Administrative & Indirect Expenses', 'Expenses');
        $pfPayableLedger = $accountingService->getOrCreateStandardLedger($companyId, 'PF & ESI Payable', 'Duties & Taxes', 'Liabilities');
        $bankLedger = Ledger::findOrFail($data['payment_ledger_id']);

        // 1. Advance Recovery
        if ($payroll->advance_deduction > 0) {
            $activeAdvances = SalaryAdvance::where('company_id', $companyId)
                ->where('staff_id', $payroll->staff_id)
                ->whereIn('status', ['approved', 'disbursed'])
                ->where('balance_amount', '>', 0)
                ->get();

            $toRecover = (float)$payroll->advance_deduction;
            foreach ($activeAdvances as $adv) {
                if ($toRecover <= 0) break;
                $deduct = min((float)$adv->balance_amount, $toRecover);
                $adv->recovered_amount += $deduct;
                $adv->balance_amount = max(0, (float)$adv->amount - (float)$adv->recovered_amount);
                if ($adv->balance_amount <= 0) {
                    $adv->status = 'recovered';
                }
                $adv->save();
                $toRecover -= $deduct;
            }
        }

        // 2. Staff Loan Installment Deduction
        if ($payroll->loan_deduction > 0) {
            $dueInstallments = StaffLoanInstallment::whereHas('loan', function ($q) use ($companyId, $payroll) {
                    $q->where('company_id', $companyId)
                        ->where('staff_id', $payroll->staff_id)
                        ->where('status', 'active');
                })
                ->where('due_month', $payroll->salary_month)
                ->where('status', 'pending')
                ->get();

            foreach ($dueInstallments as $inst) {
                $inst->update([
                    'status' => 'deducted',
                    'payroll_id' => $payroll->id,
                    'deducted_at' => now(),
                ]);

                $loan = $inst->loan;
                if ($loan) {
                    $loan->outstanding_balance = max(0, (float)$loan->outstanding_balance - (float)$inst->amount);
                    if ($loan->outstanding_balance <= 0) {
                        $loan->status = 'closed';
                    }
                    $loan->save();
                }
            }
        }

        // 3. Balanced Double-Entry Voucher
        $totalSalaryExpenditure = max(0, (float)$payroll->gross_salary - (float)$payroll->lop_deduction);

        // Check accrued factory/manufacturing labor for this employee in this salary month
        $mfgAccruedWage = (float)\App\Models\DailyOperationEmployee::where('staff_id', $payroll->staff_id)
            ->whereHas('dailyOperation', function ($q) use ($companyId, $payroll) {
                $q->where('company_id', $companyId)
                    ->where('stock_posted', true)
                    ->where('operation_date', 'like', "{$payroll->salary_month}%");
            })
            ->sum('wage_earned');

        // Check if employee is classified as factory/production worker
        $isFactoryWorker = in_array($payroll->staff->employee_type ?? '', ['piece_rate', 'hourly', 'daily_wage'])
            || str_contains(strtolower($payroll->staff->department?->name ?? ''), 'production')
            || str_contains(strtolower($payroll->staff->department?->name ?? ''), 'manufacturing')
            || str_contains(strtolower($payroll->staff->department?->name ?? ''), 'factory');

        $mfgLaborSettlement = 0;
        if ($mfgAccruedWage > 0) {
            $mfgLaborSettlement = min($totalSalaryExpenditure, $mfgAccruedWage);
        } elseif ($isFactoryWorker && ($payroll->staff->employee_type ?? '') === 'piece_rate') {
            $mfgLaborSettlement = $totalSalaryExpenditure;
        }

        $mfgLaborSettlement = round($mfgLaborSettlement, 2);
        $adminSalaryExpense = round($totalSalaryExpenditure - $mfgLaborSettlement, 2);

        $lines = [];
        if ($mfgLaborSettlement > 0) {
            $mfgLaborPayableLedger = $accountingService->getOrCreateStandardLedger(
                $companyId, 'Manufacturing Labor Payable', 'Current Liabilities', 'Liabilities'
            );
            $lines[] = [
                'ledger_id' => $mfgLaborPayableLedger->id,
                'debit' => $mfgLaborSettlement,
                'credit' => 0,
                'narration' => "Settlement of accrued direct manufacturing labor for {$payroll->staff->full_name} ({$payroll->salary_month})",
            ];
        }

        if ($adminSalaryExpense > 0) {
            $lines[] = [
                'ledger_id' => $salaryExpLedger->id,
                'debit' => $adminSalaryExpense,
                'credit' => 0,
                'narration' => "Salary Expense for {$payroll->staff->full_name} net of LOP",
            ];
        }

        $statutoryDeductions = (float)$payroll->pf_deduction + (float)$payroll->esi_deduction;
        if ($statutoryDeductions > 0) {
            $lines[] = ['ledger_id' => $pfPayableLedger->id, 'debit' => 0, 'credit' => $statutoryDeductions, 'narration' => 'PF & ESI Payable'];
        }

        if ($payroll->advance_deduction > 0) {
            $advanceAssetLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Salary Advances to Staff', 'Loans & Advances (Asset)', 'Assets');
            $lines[] = ['ledger_id' => $advanceAssetLedger->id, 'debit' => 0, 'credit' => (float)$payroll->advance_deduction, 'narration' => "Advance recovery from {$payroll->staff->full_name}"];
        }

        if ($payroll->loan_deduction > 0) {
            $loanAssetLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Staff Loan Receivables', 'Loans & Advances (Asset)', 'Assets');
            $lines[] = ['ledger_id' => $loanAssetLedger->id, 'debit' => 0, 'credit' => (float)$payroll->loan_deduction, 'narration' => "Loan EMI recovery from {$payroll->staff->full_name}"];
        }

        $lines[] = ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => (float)$payroll->net_salary, 'narration' => "Disbursement to {$payroll->staff->full_name} via {$data['payment_mode']}"];

        $jv = $accountingService->createJournalEntry(
            $companyId,
            $payroll->branch_id,
            'Payroll',
            $data['payment_date'],
            "Salary disbursement for {$payroll->staff->full_name} ({$payroll->salary_month})",
            $lines,
            'Payroll',
            $payroll->id,
            "PAYR-{$payroll->staff->employee_id}-{$payroll->salary_month}"
        );

        $payroll->update([
            'payroll_status' => 'paid',
            'payment_date' => $data['payment_date'],
            'payment_mode' => $data['payment_mode'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'journal_entry_id' => $jv->id,
            'manufacturing_labor_settled' => $mfgLaborSettlement,
            'administrative_salary_expense' => $adminSalaryExpense,
        ]);
    }
}
