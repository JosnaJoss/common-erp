<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CapitalInvestment;
use App\Models\Investor;
use App\Models\Ledger;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapitalController extends Controller
{
    /**
     * F1. Capital & Investor Dashboard
     */
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $investors = Investor::where('company_id', $companyId)
            ->with(['ledger', 'investments'])
            ->get();

        $query = CapitalInvestment::where('company_id', $companyId)
            ->with(['investor.ledger', 'depositLedger', 'journalEntry']);

        if ($request->filled('investor_id')) {
            $query->where('investor_id', $request->get('investor_id'));
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->get('transaction_type'));
        }

        $transactions = $query->latest('transaction_date')->paginate(15);

        $bankLedgers = Ledger::where('company_id', $companyId)
            ->where('is_bank_or_cash', true)
            ->where('status', 'active')
            ->get();

        // Calculate summary metrics
        $totalCapital = $investors->sum('total_capital_contributed');
        $totalDrawings = $investors->sum('total_drawings');
        $netCapital = $investors->sum('current_balance');
        $investorCount = $investors->count();

        $stats = [
            'total_capital' => $totalCapital,
            'total_drawings' => $totalDrawings,
            'net_capital' => $netCapital,
            'investor_count' => $investorCount,
        ];

        return view('accounts.capital.index', compact(
            'investors',
            'transactions',
            'bankLedgers',
            'stats'
        ));
    }

    /**
     * F1. Store new Investor / Partner
     */
    public function investorStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'investor_type' => 'required|in:Partner,Director,Proprietor,Shareholder,External Investor',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:15',
            'aadhaar_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'shareholding_percentage' => 'nullable|numeric|min:0|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $accountingService = new AccountingService();
        $opening = (float)($data['opening_balance'] ?? 0);

        return DB::transaction(function () use ($companyId, $data, $accountingService, $opening) {
            // Auto create dedicated Equity / Capital Ledger
            $ledger = $accountingService->getOrCreateStandardLedger(
                $companyId,
                "Capital Account - {$data['name']}",
                'Capital Account',
                'Liabilities'
            );

            if ($opening > 0) {
                $ledger->opening_balance = $opening;
                $ledger->balance_type = 'credit';
                $ledger->current_balance = $opening;
                $ledger->save();
            }

            $investor = Investor::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'investor_type' => $data['investor_type'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'pan_number' => $data['pan_number'] ?? null,
                'aadhaar_number' => $data['aadhaar_number'] ?? null,
                'address' => $data['address'] ?? null,
                'shareholding_percentage' => $data['shareholding_percentage'] ?? 0,
                'total_capital_contributed' => $opening,
                'total_drawings' => 0,
                'current_balance' => $opening,
                'ledger_id' => $ledger->id,
                'status' => 'active',
            ]);

            AuditLog::log('finance', 'create_investor', "Created Investor/Partner {$investor->name} ({$investor->investor_type}) with equity ledger #{$ledger->id}");
            return back()->with('success', "Investor / Partner '{$investor->name}' registered successfully.");
        });
    }

    /**
     * F1. Update Investor
     */
    public function investorUpdate(Request $request, Investor $investor)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($investor->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'investor_type' => 'required|in:Partner,Director,Proprietor,Shareholder,External Investor',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:15',
            'shareholding_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $investor->update($data);

        if ($investor->ledger) {
            $investor->ledger->update(['name' => "Capital Account - {$data['name']}"]);
        }

        return back()->with('success', "Investor '{$investor->name}' details updated.");
    }

    /**
     * Show individual investor profile
     */
    public function investorShow(Investor $investor)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($investor->company_id != $companyId) {
            abort(403);
        }

        $investor->load(['ledger', 'investments.depositLedger', 'investments.journalEntry']);
        return view('accounts.capital.show', compact('investor'));
    }

    /**
     * F2, F3 & F4. Record Capital Contribution or Drawings & Post Double-Entry Journal
     */
    public function transactionStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'transaction_type' => 'required|in:contribution,drawings',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:Bank Transfer,Cash,Cheque,UPI,Other',
            'deposit_ledger_id' => 'required|exists:ledgers,id',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'required|string|max:255',
        ]);

        $investor = Investor::findOrFail($data['investor_id']);
        if ($investor->company_id != $companyId) {
            abort(403);
        }

        $depositLedger = Ledger::findOrFail($data['deposit_ledger_id']);
        $amount = (float)$data['amount'];
        $type = $data['transaction_type'];

        // Validate drawings against balance
        if ($type === 'drawings' && $amount > (float)$investor->current_balance) {
            return back()->withInput()->with('error', "Drawings amount (₹" . number_format($amount, 2) . ") exceeds current capital balance of ₹" . number_format($investor->current_balance, 2));
        }

        return DB::transaction(function () use ($companyId, $data, $investor, $depositLedger, $amount, $type) {
            $accountingService = new AccountingService();
            $investorLedger = $investor->ledger ?: $accountingService->getOrCreateStandardLedger(
                $companyId,
                "Capital Account - {$investor->name}",
                'Capital Account',
                'Liabilities'
            );

            $prefix = $type === 'contribution' ? 'CAP' : 'DRW';
            $invNo = $prefix . '-' . date('Y') . '-' . sprintf('%04d', CapitalInvestment::where('company_id', $companyId)->count() + 1);

            $jv = null;

            if ($type === 'contribution') {
                // Dr Bank/Cash, Cr Investor Capital
                $jv = $accountingService->createJournalEntry(
                    $companyId,
                    null,
                    'Receipt',
                    $data['transaction_date'],
                    "Capital Contribution by {$investor->name} ({$invNo}): {$data['remarks']}",
                    [
                        ['ledger_id' => $depositLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => "Funds received in {$depositLedger->name} via {$data['payment_mode']}"],
                        ['ledger_id' => $investorLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => "Capital Contribution credited to {$investor->name}"],
                    ],
                    'CapitalInvestment',
                    null,
                    $invNo
                );

                $investor->increment('total_capital_contributed', $amount);
                $investor->increment('current_balance', $amount);
            } else {
                // Dr Investor Capital, Cr Bank/Cash
                $jv = $accountingService->createJournalEntry(
                    $companyId,
                    null,
                    'Payment',
                    $data['transaction_date'],
                    "Partner Drawings by {$investor->name} ({$invNo}): {$data['remarks']}",
                    [
                        ['ledger_id' => $investorLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => "Drawings debited to {$investor->name}"],
                        ['ledger_id' => $depositLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => "Funds disbursed from {$depositLedger->name} via {$data['payment_mode']}"],
                    ],
                    'CapitalInvestment',
                    null,
                    $invNo
                );

                $investor->increment('total_drawings', $amount);
                $investor->decrement('current_balance', $amount);
            }

            $dbType = match($type) {
                'contribution', 'Capital Contribution' => 'Capital Contribution',
                'drawings', 'Partner Drawings' => 'Partner Drawings',
                default => 'Capital Contribution'
            };

            $dbPaymentMode = match($data['payment_mode']) {
                'Bank Transfer', 'UPI', 'Other' => 'Bank Transfer',
                'Cheque' => 'Cheque',
                'Cash' => 'Cash',
                'Asset Transfer' => 'Asset Transfer',
                default => 'Bank Transfer'
            };

            $investment = CapitalInvestment::create([
                'company_id' => $companyId,
                'investor_id' => $investor->id,
                'investment_no' => $invNo,
                'transaction_date' => $data['transaction_date'],
                'transaction_type' => $dbType,
                'amount' => $amount,
                'payment_mode' => $dbPaymentMode,
                'deposit_ledger_id' => $depositLedger->id,
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'],
                'journal_entry_id' => $jv->id,
                'created_by' => auth()->id(),
            ]);

            $jv->update(['reference_id' => $investment->id]);

            AuditLog::log('finance', 'record_capital_transaction', "Recorded {$type} {$invNo} of ₹{$amount} for {$investor->name}");
            return back()->with('success', ucfirst($type) . " voucher {$invNo} recorded and double-entry voucher posted.");
        });
    }

    /**
     * F5. Capital Statement & Investor Reports
     */
    public function statement(Request $request, ?Investor $investor = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $investors = Investor::where('company_id', $companyId)->get();

        $selectedInvestorId = $request->get('investor_id', $investor?->id ?: $investors->first()?->id);
        $selectedInvestor = $selectedInvestorId ? Investor::find($selectedInvestorId) : null;

        $fromDate = $request->get('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $transactions = collect();
        $openingBalance = 0;
        $totalContributions = 0;
        $totalDrawings = 0;
        $closingBalance = 0;

        if ($selectedInvestor) {
            // Prior contributions & drawings before fromDate
            $priorContributions = (float)CapitalInvestment::where('investor_id', $selectedInvestor->id)
                ->where('transaction_type', 'contribution')
                ->where('transaction_date', '<', $fromDate)
                ->sum('amount');

            $priorDrawings = (float)CapitalInvestment::where('investor_id', $selectedInvestor->id)
                ->where('transaction_type', 'drawings')
                ->where('transaction_date', '<', $fromDate)
                ->sum('amount');

            $openingBalance = max(0, ((float)$selectedInvestor->ledger?->opening_balance ?: 0) + $priorContributions - $priorDrawings);

            $transactions = CapitalInvestment::where('investor_id', $selectedInvestor->id)
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->with(['depositLedger', 'journalEntry'])
                ->orderBy('transaction_date')
                ->get();

            $totalContributions = $transactions->where('transaction_type', 'contribution')->sum('amount');
            $totalDrawings = $transactions->where('transaction_type', 'drawings')->sum('amount');
            $closingBalance = $openingBalance + $totalContributions - $totalDrawings;
        }

        // Company-wide summary table
        $companyCapitalSummary = $investors->map(function ($inv) {
            return [
                'name' => $inv->name,
                'type' => $inv->investor_type,
                'share_pct' => (float)$inv->shareholding_percentage,
                'contributions' => (float)$inv->total_capital_contributed,
                'drawings' => (float)$inv->total_drawings,
                'closing_balance' => (float)$inv->current_balance,
            ];
        });

        $totalEquity = $companyCapitalSummary->sum('closing_balance');

        return view('accounts.capital.statement', compact(
            'investors',
            'selectedInvestor',
            'fromDate',
            'toDate',
            'openingBalance',
            'transactions',
            'totalContributions',
            'totalDrawings',
            'closingBalance',
            'companyCapitalSummary',
            'totalEquity'
        ));
    }
}
