<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use App\Models\AuditLog;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Ledger;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    // Chart of Accounts
    public function chartOfAccounts()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $groups = AccountGroup::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->with(['children.ledgers', 'ledgers'])
            ->get();

        return view('accounts.chart_of_accounts', compact('groups'));
    }

    // Ledgers
    public function ledgers(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = Ledger::where('company_id', $companyId)->with('accountGroup');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('bank_account_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('group_id')) {
            $query->where('account_group_id', $request->get('group_id'));
        }

        $ledgers = $query->paginate(20);
        $groups = AccountGroup::where('company_id', $companyId)->get();

        return view('accounts.ledgers.index', compact('ledgers', 'groups'));
    }

    public function ledgerStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'account_group_id' => 'required|exists:account_groups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'opening_balance' => 'required|numeric',
            'balance_type' => 'required|in:debit,credit',
            'is_bank_or_cash' => 'nullable|boolean',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_ifsc' => 'nullable|string|max:30',
            'bank_branch' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        $data['is_bank_or_cash'] = $request->boolean('is_bank_or_cash');
        $data['current_balance'] = $data['opening_balance'];

        $ledger = Ledger::create($data);
        AuditLog::log('accounts', 'create_ledger', "Created ledger {$ledger->name}");

        return back()->with('success', 'Ledger created successfully.');
    }

    public function ledgerStatement(Request $request, Ledger $ledger)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $lines = JournalLine::where('ledger_id', $ledger->id)
            ->whereHas('entry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('voucher_date', [$startDate, $endDate]);
            })
            ->with('entry')
            ->get()
            ->sortBy(fn($l) => $l->entry->voucher_date);

        return view('accounts.ledgers.statement', compact('ledger', 'lines', 'startDate', 'endDate'));
    }

    // Journal Entries (Vouchers)
    public function journalEntries(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = JournalEntry::where('company_id', $companyId)->with(['lines.ledger', 'creator']);

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->get('voucher_type'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('voucher_date', [$request->get('start_date'), $request->get('end_date')]);
        }

        $entries = $query->latest('voucher_date')->latest('id')->paginate(20);
        return view('accounts.vouchers.index', compact('entries'));
    }

    public function journalCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $ledgers = Ledger::where('company_id', $companyId)->where('status', 'active')->get();
        return view('accounts.vouchers.create', compact('ledgers'));
    }

    public function journalStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'voucher_type' => 'required|in:Journal,Payment,Receipt,Contra,Expense,Income',
            'voucher_date' => 'required|date',
            'narration' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.ledger_id' => 'required|exists:ledgers,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.narration' => 'nullable|string',
        ]);

        try {
            $accountingService = new AccountingService();
            $entry = $accountingService->createJournalEntry(
                $companyId,
                null,
                $data['voucher_type'],
                $data['voucher_date'],
                $data['narration'] ?? null,
                $data['lines']
            );

            AuditLog::log('accounts', 'create_voucher', "Created voucher {$entry->voucher_no} ({$entry->voucher_type})");
            return redirect()->route('accounts.vouchers.show', $entry)->with('success', "Voucher {$entry->voucher_no} posted successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }
    }

    public function journalShow(JournalEntry $entry)
    {
        $entry->load(['lines.ledger.accountGroup', 'creator', 'company']);
        return view('accounts.vouchers.show', compact('entry'));
    }

    // Financial Years
    public function financialYears()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $years = FinancialYear::where('company_id', $companyId)->orderByDesc('start_date')->get();
        return view('accounts.financial_years.index', compact('years'));
    }

    public function financialYearStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'title' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $data['company_id'] = $companyId;
        $data['is_closed'] = false;

        FinancialYear::create($data);
        AuditLog::log('accounts', 'create_fy', "Created financial year {$data['title']}");

        return back()->with('success', 'Financial Year created successfully.');
    }
}

