<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Ledger;
use App\Models\PettyCashAccount;
use App\Models\PettyCashCategory;
use App\Models\PettyCashReplenishment;
use App\Models\PettyCashVoucher;
use App\Models\Staff;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PettyCashController extends Controller
{
    /**
     * E1. Petty Cash Dashboard & Overview
     */
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $accounts = PettyCashAccount::where('company_id', $companyId)
            ->with(['custodian', 'branch', 'ledger'])
            ->get();

        $query = PettyCashVoucher::where('company_id', $companyId)
            ->with(['account', 'category.ledger', 'creator', 'journalEntry']);

        if ($request->filled('account_id')) {
            $query->where('petty_cash_account_id', $request->get('account_id'));
        }

        if ($request->filled('from_date')) {
            $query->where('voucher_date', '>=', $request->get('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('voucher_date', '<=', $request->get('to_date'));
        }

        $vouchers = $query->latest('voucher_date')->paginate(15);

        $categories = PettyCashCategory::where('company_id', $companyId)
            ->with('ledger')
            ->get();

        // If no default categories exist for this company, auto-seed standard ones
        if ($categories->isEmpty()) {
            $this->seedDefaultCategories($companyId);
            $categories = PettyCashCategory::where('company_id', $companyId)->with('ledger')->get();
        }

        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->where('status', 'active')->get();

        // Compute metrics
        $totalFloat = $accounts->sum('float_limit');
        $totalCurrentCash = $accounts->sum('current_balance');
        $monthSpent = PettyCashVoucher::where('company_id', $companyId)
            ->whereMonth('voucher_date', now()->month)
            ->whereYear('voucher_date', now()->year)
            ->sum('amount');
        $lowBalanceCount = $accounts->filter(function ($a) {
            return $a->float_limit > 0 && ($a->current_balance / $a->float_limit) <= 0.20;
        })->count();

        $stats = [
            'total_float' => $totalFloat,
            'total_current_cash' => $totalCurrentCash,
            'month_spent' => $monthSpent,
            'low_balance_count' => $lowBalanceCount,
        ];

        return view('accounts.petty_cash.index', compact(
            'accounts',
            'vouchers',
            'categories',
            'branches',
            'staffMembers',
            'bankLedgers',
            'stats'
        ));
    }

    /**
     * E1. Store a new Petty Cash Account
     */
    public function accountsStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'custodian_staff_id' => 'nullable|exists:staff,id',
            'float_limit' => 'required|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $accountingService = new AccountingService();
        $opening = (float)($data['opening_balance'] ?? 0);

        return DB::transaction(function () use ($companyId, $data, $accountingService, $opening) {
            $code = 'PCA-' . sprintf('%03d', PettyCashAccount::where('company_id', $companyId)->count() + 1);

            // Create dedicated ledger under Cash & Bank Balances (Current Assets)
            $ledger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                "Petty Cash - {$data['name']}",
                'Cash & Bank Balances',
                'Assets'
            );

            $account = PettyCashAccount::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'custodian_staff_id' => $data['custodian_staff_id'] ?? null,
                'name' => $data['name'],
                'code' => $code,
                'float_limit' => $data['float_limit'],
                'current_balance' => $opening,
                'ledger_id' => $ledger->id,
                'status' => 'active',
            ]);

            if ($opening > 0) {
                $ledger->opening_balance = $opening;
                $ledger->balance_type = 'debit';
                $ledger->current_balance = $opening;
                $ledger->save();
            }

            AuditLog::log('finance', 'create_petty_cash_account', "Created Petty Cash Account {$account->name} ({$code}) with float limit ₹{$account->float_limit}");
            return back()->with('success', "Petty Cash account '{$account->name}' created successfully.");
        });
    }

    /**
     * E2. Store a custom Expense Category
     */
    public function categoriesStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'ledger_id' => 'nullable|exists:ledgers,id',
        ]);

        $accountingService = new AccountingService();
        $ledgerId = $data['ledger_id'] ?? null;

        if (!$ledgerId) {
            $ledger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                $data['name'],
                'Administrative & Indirect Expenses',
                'Expenses'
            );
            $ledgerId = $ledger->id;
        }

        PettyCashCategory::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'ledger_id' => $ledgerId,
            'status' => 'active',
        ]);

        return back()->with('success', "Petty Cash Category '{$data['name']}' created.");
    }

    /**
     * E3. Voucher Creation Form
     */
    public function voucherCreate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $accounts = PettyCashAccount::where('company_id', $companyId)->where('status', 'active')->get();
        $categories = PettyCashCategory::where('company_id', $companyId)->where('status', 'active')->get();
        $selectedAccountId = $request->get('account_id', $accounts->first()?->id);

        return view('accounts.petty_cash.vouchers.create', compact('accounts', 'categories', 'selectedAccountId'));
    }

    /**
     * E3 & E4. Store Voucher & Post Double-Entry Journal Entry
     */
    public function voucherStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'petty_cash_account_id' => 'required|exists:petty_cash_accounts,id',
            'category_id' => 'required|exists:petty_cash_categories,id',
            'voucher_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'paid_to' => 'required|string|max:150',
            'remarks' => 'required|string',
            'receipt_doc' => 'nullable|file|mimes:jpeg,png,pdf,jpg|max:5120',
        ]);

        $account = PettyCashAccount::findOrFail($data['petty_cash_account_id']);
        if ($account->company_id != $companyId) {
            abort(403);
        }

        $category = PettyCashCategory::findOrFail($data['category_id']);
        $amount = (float)$data['amount'];

        if ($amount > (float)$account->current_balance) {
            return back()->withInput()->with('error', "Insufficient petty cash balance! Available: ₹" . number_format($account->current_balance, 2) . ", Requested: ₹" . number_format($amount, 2));
        }

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['voucher_date'])
            ->where('end_date', '>=', $data['voucher_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->with('error', 'Cannot record petty cash voucher in a closed financial year period.');
        }

        return DB::transaction(function () use ($companyId, $data, $account, $category, $amount, $request) {
            $voucherNo = 'PCV-' . date('Y') . '-' . sprintf('%04d', PettyCashVoucher::where('company_id', $companyId)->count() + 1);

            $receiptPath = null;
            if ($request->hasFile('receipt_doc')) {
                $receiptPath = $request->file('receipt_doc')->store('petty_cash', 'public');
            }

            // Accounting Entry: Dr Expense Ledger, Cr Petty Cash Account Ledger
            $accountingService = new AccountingService();
            $expenseLedger = $category->ledger ?: $accountingService->getOrCreateStandardLedger(
                $companyId,
                $category->name,
                'Administrative & Indirect Expenses',
                'Expenses'
            );

            $pettyCashLedger = $account->ledger ?: $accountingService->getOrCreateStandardLedger(
                $companyId,
                "Petty Cash - {$account->name}",
                'Cash & Bank Balances',
                'Assets'
            );

            $jv = $accountingService->createJournalEntry(
                $companyId,
                $account->branch_id,
                'Payment',
                $data['voucher_date'],
                "Petty Cash Voucher {$voucherNo} paid to {$data['paid_to']}: {$data['remarks']}",
                [
                    ['ledger_id' => $expenseLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => "Petty expense for {$category->name}"],
                    ['ledger_id' => $pettyCashLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => "Disbursement from {$account->name}"],
                ],
                'PettyCashVoucher',
                null,
                $voucherNo
            );

            $voucher = PettyCashVoucher::create([
                'company_id' => $companyId,
                'branch_id' => $account->branch_id,
                'petty_cash_account_id' => $account->id,
                'voucher_no' => $voucherNo,
                'voucher_date' => $data['voucher_date'],
                'expense_category' => $category->name,
                'category_id' => $category->id,
                'amount' => $amount,
                'paid_to' => $data['paid_to'],
                'receipt_doc' => $receiptPath,
                'remarks' => $data['remarks'],
                'status' => 'approved',
                'journal_entry_id' => $jv->id,
                'created_by' => auth()->id(),
            ]);

            $jv->update(['reference_id' => $voucher->id]);

            // Deduct from live balance
            $account->decrement('current_balance', $amount);

            AuditLog::log('finance', 'create_petty_cash_voucher', "Created Petty Cash Voucher {$voucherNo} for ₹{$amount} paid to {$data['paid_to']}");
            return redirect()->route('accounts.petty_cash.vouchers.show', $voucher)
                ->with('success', "Petty Cash Voucher {$voucherNo} issued and journal entry posted.");
        });
    }

    /**
     * E3. Show Voucher
     */
    public function voucherShow(PettyCashVoucher $voucher)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($voucher->company_id != $companyId) {
            abort(403);
        }

        $voucher->load(['account.custodian', 'account.branch', 'category', 'journalEntry.lines.ledger', 'creator']);
        return view('accounts.petty_cash.vouchers.show', compact('voucher'));
    }

    /**
     * E6. Printable Petty Cash Voucher
     */
    public function voucherPrint(PettyCashVoucher $voucher)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($voucher->company_id != $companyId) {
            abort(403);
        }

        $voucher->load(['account.custodian', 'account.company', 'category', 'creator']);
        return view('accounts.petty_cash.vouchers.print', compact('voucher'));
    }

    /**
     * E5. Float Replenishment / Reimbursement
     */
    public function replenishStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'petty_cash_account_id' => 'required|exists:petty_cash_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_ledger_id' => 'required|exists:ledgers,id',
            'replenishment_date' => 'required|date',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $account = PettyCashAccount::findOrFail($data['petty_cash_account_id']);
        if ($account->company_id != $companyId) {
            abort(403);
        }

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['replenishment_date'])
            ->where('end_date', '>=', $data['replenishment_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->with('error', 'Cannot record replenishment in a closed financial year period.');
        }

        $bankLedger = Ledger::findOrFail($data['bank_ledger_id']);
        $amount = (float)$data['amount'];

        return DB::transaction(function () use ($companyId, $data, $account, $bankLedger, $amount) {
            $repNo = 'PCR-' . date('Y') . '-' . sprintf('%04d', PettyCashReplenishment::where('company_id', $companyId)->count() + 1);

            $accountingService = new AccountingService();
            $pettyCashLedger = $account->ledger ?: $accountingService->getOrCreateStandardLedger(
                $companyId,
                "Petty Cash - {$account->name}",
                'Cash & Bank Balances',
                'Assets'
            );

            // Double Entry: Dr Petty Cash Ledger, Cr Bank Ledger
            $jv = $accountingService->createJournalEntry(
                $companyId,
                $account->branch_id,
                'Contra',
                $data['replenishment_date'],
                "Petty Cash Replenishment {$repNo} for {$account->name} via {$bankLedger->name}",
                [
                    ['ledger_id' => $pettyCashLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => "Cash received for replenishment"],
                    ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => "Funds disbursed from bank ({$data['transaction_reference']})"],
                ],
                'PettyCashReplenishment',
                null,
                $repNo
            );

            $replenishment = PettyCashReplenishment::create([
                'company_id' => $companyId,
                'petty_cash_account_id' => $account->id,
                'replenishment_no' => $repNo,
                'replenishment_date' => $data['replenishment_date'],
                'amount' => $amount,
                'bank_ledger_id' => $bankLedger->id,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'journal_entry_id' => $jv->id,
                'created_by' => auth()->id(),
            ]);

            $jv->update(['reference_id' => $replenishment->id]);

            // Increment live balance
            $account->increment('current_balance', $amount);

            AuditLog::log('finance', 'replenish_petty_cash', "Replenished ₹{$amount} to {$account->name} ({$repNo}) from {$bankLedger->name}");
            return back()->with('success', "Petty Cash float replenished by ₹" . number_format($amount, 2) . ". Journal voucher {$jv->voucher_no} posted.");
        });
    }

    /**
     * E8. Daily Petty Cash Book & Register Report
     */
    public function daybook(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $accounts = PettyCashAccount::where('company_id', $companyId)->get();
        $selectedAccountId = $request->get('account_id', $accounts->first()?->id);
        $selectedAccount = $selectedAccountId ? PettyCashAccount::find($selectedAccountId) : null;

        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $vouchers = collect();
        $replenishments = collect();
        $openingBalance = 0;

        if ($selectedAccount) {
            // Opening balance before fromDate:
            // opening_balance + prior replenishments - prior vouchers
            $priorReplenishments = (float)PettyCashReplenishment::where('petty_cash_account_id', $selectedAccount->id)
                ->where('replenishment_date', '<', $fromDate)
                ->sum('amount');

            $priorVouchers = (float)PettyCashVoucher::where('petty_cash_account_id', $selectedAccount->id)
                ->where('voucher_date', '<', $fromDate)
                ->sum('amount');

            $openingBalance = max(0, ((float)$selectedAccount->ledger?->opening_balance ?: 0) + $priorReplenishments - $priorVouchers);

            $vouchers = PettyCashVoucher::where('petty_cash_account_id', $selectedAccount->id)
                ->whereBetween('voucher_date', [$fromDate, $toDate])
                ->with(['category', 'creator'])
                ->orderBy('voucher_date')
                ->get();

            $replenishments = PettyCashReplenishment::where('petty_cash_account_id', $selectedAccount->id)
                ->whereBetween('replenishment_date', [$fromDate, $toDate])
                ->with('bankLedger')
                ->orderBy('replenishment_date')
                ->get();
        }

        $totalPayments = $vouchers->sum('amount');
        $totalReceipts = $replenishments->sum('amount');
        $closingBalance = $openingBalance + $totalReceipts - $totalPayments;

        // Category breakdown
        $categoryBreakdown = $vouchers->groupBy('expense_category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'count' => count($items),
                'total' => $items->sum('amount'),
            ];
        });

        return view('accounts.petty_cash.daybook', compact(
            'accounts',
            'selectedAccount',
            'fromDate',
            'toDate',
            'openingBalance',
            'vouchers',
            'replenishments',
            'totalPayments',
            'totalReceipts',
            'closingBalance',
            'categoryBreakdown'
        ));
    }

    private function seedDefaultCategories(int $companyId): void
    {
        $defaults = [
            'Office Refreshments & Tea',
            'Local Conveyance & Travel',
            'Office Stationery & Printing',
            'Courier & Postage',
            'Repairs & Office Maintenance',
            'Staff Welfare & Pantry',
            'Miscellaneous Cash Expenses',
        ];

        $accountingService = new AccountingService();
        foreach ($defaults as $name) {
            $ledger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                $name,
                'Administrative & Indirect Expenses',
                'Expenses'
            );

            PettyCashCategory::firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['ledger_id' => $ledger->id, 'status' => 'active']
            );
        }
    }
}
