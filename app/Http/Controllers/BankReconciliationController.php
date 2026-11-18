<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Ledger;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankReconciliationController extends Controller
{
    /**
     * D1. Bank Reconciliation Dashboard
     */
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $query = BankReconciliation::where('company_id', $companyId)
            ->with(['bankLedger', 'user', 'items']);

        if ($request->filled('ledger_id')) {
            $query->where('ledger_id', $request->get('ledger_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $reconciliations = $query->latest('statement_date')->paginate(15);

        $bankLedgers = Ledger::where('company_id', $companyId)
            ->where('is_bank_or_cash', true)
            ->where('status', 'active')
            ->get();

        $stats = [
            'total' => BankReconciliation::where('company_id', $companyId)->count(),
            'completed' => BankReconciliation::where('company_id', $companyId)->whereIn('status', ['reconciled', 'completed'])->count(),
            'draft' => BankReconciliation::where('company_id', $companyId)->whereIn('status', ['in_progress', 'draft'])->count(),
            'latest_reconciled' => BankReconciliation::where('company_id', $companyId)
                ->whereIn('status', ['reconciled', 'completed'])
                ->latest('statement_date')
                ->value('statement_date'),
        ];

        return view('accounts.bank_reconciliation.index', compact('reconciliations', 'bankLedgers', 'stats'));
    }

    /**
     * D6. Create Reconciliation Setup Screen
     */
    public function create()
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $bankLedgers = Ledger::where('company_id', $companyId)
            ->where('is_bank_or_cash', true)
            ->where('status', 'active')
            ->get();

        return view('accounts.bank_reconciliation.create', compact('bankLedgers'));
    }

    /**
     * Store and initialize reconciliation workspace with book entries
     */
    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'ledger_id' => 'required|exists:ledgers,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $ledger = Ledger::findOrFail($data['ledger_id']);
        if ($ledger->company_id != $companyId) {
            abort(403);
        }

        // Calculate book balance as of statement date
        $statementDate = Carbon::parse($data['statement_date'])->toDateString();
        $bookBalance = $this->calculateBookBalanceAsOf($ledger, $statementDate);

        return DB::transaction(function () use ($companyId, $data, $ledger, $statementDate, $bookBalance) {
            $recCount = BankReconciliation::where('company_id', $companyId)->count() + 1;
            $recNo = 'BRS-' . date('Y') . '-' . sprintf('%04d', $recCount);

            $reconciliation = BankReconciliation::create([
                'company_id' => $companyId,
                'ledger_id' => $ledger->id,
                'reconciliation_no' => $recNo,
                'statement_date' => $statementDate,
                'statement_balance' => (float)$data['statement_balance'],
                'book_balance' => $bookBalance,
                'reconciled_balance' => $bookBalance,
                'difference' => (float)$data['statement_balance'] - $bookBalance,
                'status' => 'in_progress',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Populate all journal lines up to statement date that are not cleared in any completed reconciliation
            $clearedJournalLineIds = BankReconciliationItem::whereHas('bankReconciliation', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['reconciled', 'completed']);
            })->where('is_cleared', true)->pluck('journal_line_id')->filter()->toArray();

            $journalLines = JournalLine::where('ledger_id', $ledger->id)
                ->whereHas('journalEntry', function ($q) use ($companyId, $statementDate) {
                    $q->where('company_id', $companyId)
                        ->where('voucher_date', '<=', $statementDate);
                })
                ->whereNotIn('id', $clearedJournalLineIds)
                ->with('journalEntry')
                ->get();

            foreach ($journalLines as $line) {
                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'journal_line_id' => $line->id,
                    'transaction_date' => $line->journalEntry->voucher_date,
                    'clearance_date' => null,
                    'cheque_reference' => $line->journalEntry->reference_no ?: $line->journalEntry->voucher_no,
                    'description' => $line->narration ?: $line->journalEntry->narration,
                    'source' => 'system',
                    'debit_amount' => (float)$line->debit_amount,
                    'credit_amount' => (float)$line->credit_amount,
                    'is_cleared' => false,
                ]);
            }

            $this->recalculateReconciliation($reconciliation);

            AuditLog::log('finance', 'create_bank_reconciliation', "Started Bank Reconciliation {$recNo} for {$ledger->name}");
            return redirect()->route('accounts.bank_reconciliation.show', $reconciliation)
                ->with('success', "Bank Reconciliation {$recNo} initialized with " . count($journalLines) . " book entries.");
        });
    }

    /**
     * D3 & D4. Interactive Reconciliation Workspace & Suggestions
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $bankReconciliation->load(['bankLedger', 'items.journalLine.journalEntry', 'items.matchedItem']);

        $bookItems = $bankReconciliation->items()
            ->whereIn('source', ['system', 'book'])
            ->orderBy('transaction_date')
            ->get();

        $statementItems = $bankReconciliation->items()
            ->whereIn('source', ['imported', 'statement'])
            ->orderBy('transaction_date')
            ->get();

        // D4. Auto-Matching Suggestions
        $suggestions = $this->generateMatchingSuggestions($bookItems, $statementItems);

        // BRS summary calculations
        $summary = $this->calculateBrsSummary($bankReconciliation);

        return view('accounts.bank_reconciliation.show', compact(
            'bankReconciliation',
            'bookItems',
            'statementItems',
            'suggestions',
            'summary'
        ));
    }

    /**
     * D2. Bank Statement CSV Import
     */
    public function importStatement(Request $request, BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $request->validate([
            'statement_csv' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('statement_csv');
        $originalName = $file->getClientOriginalName();
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to open uploaded statement file.');
        }

        $importedCount = 0;
        $skippedCount = 0;
        $rowIdx = 0;
        $header = null;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2048, ',')) !== false) {
                $rowIdx++;
                if ($rowIdx === 1) {
                    $header = array_map(fn($v) => strtolower(trim(str_replace([' ', '_', '-'], '', $v))), $row);
                    continue;
                }

                if (empty(array_filter($row))) {
                    continue;
                }

                // Parse columns dynamically or positionally
                $dateStr = $row[0] ?? null;
                $narration = $row[1] ?? 'Bank transaction';
                $refNo = $row[2] ?? null;
                $debit = 0;
                $credit = 0;

                if (count($row) >= 5) {
                    $debit = (float)str_replace([',', ' '], '', $row[3] ?? 0);
                    $credit = (float)str_replace([',', ' '], '', $row[4] ?? 0);
                } elseif (count($row) === 4) {
                    $amount = (float)str_replace([',', ' '], '', $row[3] ?? 0);
                    if ($amount < 0) {
                        $debit = abs($amount);
                    } else {
                        $credit = $amount;
                    }
                }

                if (!$dateStr) {
                    $skippedCount++;
                    continue;
                }

                $parsedDate = null;
                try {
                    $parsedDate = Carbon::parse($dateStr)->toDateString();
                } catch (Exception $e) {
                    $skippedCount++;
                    continue;
                }

                // Check duplicate within statement items of this reconciliation
                $exists = BankReconciliationItem::where('bank_reconciliation_id', $bankReconciliation->id)
                    ->whereIn('source', ['imported', 'statement'])
                    ->where('transaction_date', $parsedDate)
                    ->where('debit_amount', $debit)
                    ->where('credit_amount', $credit)
                    ->where('cheque_reference', $refNo)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $bankReconciliation->id,
                    'journal_line_id' => null,
                    'transaction_date' => $parsedDate,
                    'clearance_date' => null,
                    'cheque_reference' => $refNo ?: 'STMT-' . $rowIdx,
                    'description' => $narration,
                    'source' => 'imported',
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'is_cleared' => false,
                ]);

                $importedCount++;
            }

            fclose($handle);

            $bankReconciliation->update([
                'import_filename' => $originalName,
            ]);

            $this->recalculateReconciliation($bankReconciliation);
            DB::commit();

            AuditLog::log('finance', 'import_bank_statement', "Imported {$importedCount} entries from {$originalName} for {$bankReconciliation->reconciliation_no}");
            return back()->with('success', "Imported {$importedCount} statement lines successfully ({$skippedCount} skipped or duplicates).");
        } catch (Exception $e) {
            DB::rollBack();
            if ($handle) fclose($handle);
            return back()->with('error', 'Failed to import statement: ' . $e->getMessage());
        }
    }

    /**
     * D3. Manual Match Statement Item with Book Item
     */
    public function match(Request $request, BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'book_item_id' => 'required|exists:bank_reconciliation_items,id',
            'statement_item_id' => 'required|exists:bank_reconciliation_items,id',
        ]);

        $bookItem = BankReconciliationItem::where('bank_reconciliation_id', $bankReconciliation->id)
            ->where('id', $data['book_item_id'])
            ->whereIn('source', ['system', 'book'])
            ->firstOrFail();

        $stmtItem = BankReconciliationItem::where('bank_reconciliation_id', $bankReconciliation->id)
            ->where('id', $data['statement_item_id'])
            ->whereIn('source', ['imported', 'statement'])
            ->firstOrFail();

        DB::transaction(function () use ($bookItem, $stmtItem, $bankReconciliation) {
            $clearanceDate = $stmtItem->transaction_date ?: $bankReconciliation->statement_date;

            $bookItem->update([
                'matched_item_id' => $stmtItem->id,
                'is_cleared' => true,
                'clearance_date' => $clearanceDate,
            ]);

            $stmtItem->update([
                'matched_item_id' => $bookItem->id,
                'is_cleared' => true,
                'clearance_date' => $clearanceDate,
            ]);

            $this->recalculateReconciliation($bankReconciliation);
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Transactions matched and cleared successfully.');
    }

    /**
     * Unmatch a previously matched pair
     */
    public function unmatch(Request $request, BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'item_id' => 'required|exists:bank_reconciliation_items,id',
        ]);

        $item = BankReconciliationItem::where('bank_reconciliation_id', $bankReconciliation->id)
            ->where('id', $data['item_id'])
            ->firstOrFail();

        DB::transaction(function () use ($item, $bankReconciliation) {
            if ($item->matched_item_id) {
                $other = BankReconciliationItem::find($item->matched_item_id);
                if ($other) {
                    $other->update([
                        'matched_item_id' => null,
                        'is_cleared' => false,
                        'clearance_date' => null,
                    ]);
                }
            }

            $item->update([
                'matched_item_id' => null,
                'is_cleared' => false,
                'clearance_date' => null,
            ]);

            $this->recalculateReconciliation($bankReconciliation);
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Match removed.');
    }

    /**
     * D4. Direct Clearance Toggle for Book Item
     */
    public function toggleClearance(BankReconciliation $bankReconciliation, BankReconciliationItem $item)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId || $item->bank_reconciliation_id != $bankReconciliation->id) {
            abort(403);
        }

        DB::transaction(function () use ($bankReconciliation, $item) {
            $newStatus = !$item->is_cleared;
            $item->update([
                'is_cleared' => $newStatus,
                'clearance_date' => $newStatus ? $bankReconciliation->statement_date : null,
            ]);

            $this->recalculateReconciliation($bankReconciliation);
        });

        return back()->with('success', 'Clearance status updated.');
    }

    /**
     * D5. Direct Bank Charges & Interest Adjustments
     */
    public function recordAdjustment(Request $request, BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'adjustment_type' => 'required|in:bank_charges,interest_received,direct_debit,direct_deposit',
            'amount' => 'required|numeric|min:0.01',
            'adjustment_date' => 'required|date',
            'narration' => 'required|string|max:255',
            'statement_item_id' => 'nullable|exists:bank_reconciliation_items,id',
        ]);

        $accountingService = new AccountingService();
        $bankLedger = $bankReconciliation->bankLedger;

        return DB::transaction(function () use ($companyId, $data, $bankReconciliation, $accountingService, $bankLedger) {
            $amount = (float)$data['amount'];
            $date = $data['adjustment_date'];
            $narration = $data['narration'];
            $jv = null;
            $bankLine = null;

            if ($data['adjustment_type'] === 'bank_charges' || $data['adjustment_type'] === 'direct_debit') {
                $chargeLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Bank Charges & Commission', 'Administrative & Indirect Expenses', 'Expenses');
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Payment', $date,
                    $narration,
                    [
                        ['ledger_id' => $chargeLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => $narration],
                        ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Deducted by bank'],
                    ],
                    'BankReconciliation', $bankReconciliation->id, $bankReconciliation->reconciliation_no
                );
                $bankLine = $jv->lines()->where('ledger_id', $bankLedger->id)->first();
            } else {
                $interestLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Bank Interest & Other Income', 'Other & Non-Operating Income', 'Revenue');
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Receipt', $date,
                    $narration,
                    [
                        ['ledger_id' => $bankLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Credited by bank'],
                        ['ledger_id' => $interestLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => $narration],
                    ],
                    'BankReconciliation', $bankReconciliation->id, $bankReconciliation->reconciliation_no
                );
                $bankLine = $jv->lines()->where('ledger_id', $bankLedger->id)->first();
            }

            // Create book item for this newly posted adjustment and mark it cleared
            $newBookItem = BankReconciliationItem::create([
                'bank_reconciliation_id' => $bankReconciliation->id,
                'journal_line_id' => $bankLine?->id,
                'transaction_date' => $date,
                'clearance_date' => $date,
                'cheque_reference' => $jv->voucher_no,
                'description' => $narration,
                'source' => 'system',
                'debit_amount' => $bankLine?->debit_amount ?? 0,
                'credit_amount' => $bankLine?->credit_amount ?? 0,
                'is_cleared' => true,
            ]);

            // If linked to an uploaded statement item, match them
            if (!empty($data['statement_item_id'])) {
                $stmtItem = BankReconciliationItem::find($data['statement_item_id']);
                if ($stmtItem) {
                    $newBookItem->update(['matched_item_id' => $stmtItem->id]);
                    $stmtItem->update([
                        'matched_item_id' => $newBookItem->id,
                        'is_cleared' => true,
                        'clearance_date' => $date,
                    ]);
                }
            }

            $this->recalculateReconciliation($bankReconciliation);

            AuditLog::log('finance', 'bank_reconciliation_adjustment', "Posted adjustment voucher {$jv->voucher_no} for {$bankReconciliation->reconciliation_no}");
            return back()->with('success', "Adjustment voucher {$jv->voucher_no} posted and reconciled.");
        });
    }

    /**
     * D6. Finalize & Lock Reconciliation Period
     */
    public function complete(BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $this->recalculateReconciliation($bankReconciliation);

        $bankReconciliation->update([
            'status' => 'reconciled',
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id(),
        ]);

        AuditLog::log('finance', 'complete_bank_reconciliation', "Finalized and locked Bank Reconciliation {$bankReconciliation->reconciliation_no}");
        return redirect()->route('accounts.bank_reconciliation.report', $bankReconciliation)
            ->with('success', "Bank Reconciliation {$bankReconciliation->reconciliation_no} completed and locked successfully.");
    }

    /**
     * D7. Bank Reconciliation Statement (BRS) Report
     */
    public function report(BankReconciliation $bankReconciliation)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($bankReconciliation->company_id != $companyId) {
            abort(403);
        }

        $bankReconciliation->load(['bankLedger', 'company', 'user', 'items.journalLine.journalEntry']);

        $summary = $this->calculateBrsSummary($bankReconciliation);

        $unclearedCheques = $bankReconciliation->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('credit_amount', '>', 0)
            ->get();

        $unclearedDeposits = $bankReconciliation->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('debit_amount', '>', 0)
            ->get();

        return view('accounts.bank_reconciliation.report', compact(
            'bankReconciliation',
            'summary',
            'unclearedCheques',
            'unclearedDeposits'
        ));
    }

    // ==================== INTERNAL HELPER METHODS ====================

    private function calculateBookBalanceAsOf(Ledger $ledger, string $asOfDate): float
    {
        $lines = JournalLine::where('ledger_id', $ledger->id)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('voucher_date', '<=', $asOfDate);
            })
            ->selectRaw('SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit')
            ->first();

        $opening = (float)$ledger->opening_balance;
        $totalDebit = (float)($lines->total_debit ?? 0);
        $totalCredit = (float)($lines->total_credit ?? 0);

        if ($ledger->balance_type === 'credit') {
            return $opening + $totalCredit - $totalDebit;
        } else {
            return $opening + $totalDebit - $totalCredit;
        }
    }

    private function recalculateReconciliation(BankReconciliation $rec): void
    {
        $bookBalance = $this->calculateBookBalanceAsOf($rec->bankLedger, $rec->statement_date->toDateString());

        // Unpresented Cheques (Credit in books not cleared)
        $unpresentedCheques = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('credit_amount', '>', 0)
            ->sum('credit_amount');

        // Uncleared Deposits (Debit in books not cleared)
        $unclearedDeposits = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('debit_amount', '>', 0)
            ->sum('debit_amount');

        // Standard BRS formula:
        // Statement Balance = Book Balance + Unpresented Cheques - Uncleared Deposits
        $reconciledBalance = $bookBalance + $unpresentedCheques - $unclearedDeposits;
        $difference = round((float)$rec->statement_balance - $reconciledBalance, 2);

        $rec->update([
            'book_balance' => $bookBalance,
            'reconciled_balance' => $reconciledBalance,
            'difference' => $difference,
        ]);
    }

    private function calculateBrsSummary(BankReconciliation $rec): array
    {
        $bookBalance = (float)$rec->book_balance;

        $unpresentedCheques = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('credit_amount', '>', 0)
            ->sum('credit_amount');

        $unclearedDeposits = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', false)
            ->where('debit_amount', '>', 0)
            ->sum('debit_amount');

        $clearedDebits = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', true)
            ->sum('debit_amount');

        $clearedCredits = (float)$rec->items()
            ->whereIn('source', ['system', 'book'])
            ->where('is_cleared', true)
            ->sum('credit_amount');

        $reconciledBalance = $bookBalance + $unpresentedCheques - $unclearedDeposits;
        $statementBalance = (float)$rec->statement_balance;
        $difference = round($statementBalance - $reconciledBalance, 2);

        return [
            'book_balance' => $bookBalance,
            'unpresented_cheques' => $unpresentedCheques,
            'uncleared_deposits' => $unclearedDeposits,
            'cleared_debits' => $clearedDebits,
            'cleared_credits' => $clearedCredits,
            'reconciled_balance' => $reconciledBalance,
            'statement_balance' => $statementBalance,
            'difference' => $difference,
            'is_balanced' => abs($difference) < 0.01,
        ];
    }

    private function generateMatchingSuggestions($bookItems, $statementItems): array
    {
        $unmatchedBooks = $bookItems->where('is_cleared', false);
        $unmatchedStmts = $statementItems->where('is_cleared', false);

        $suggestions = [];
        $usedBookIds = [];

        foreach ($unmatchedStmts as $stmt) {
            // Find candidate in books:
            // Stmt Credit (deposit) matches Book Debit (receipt)
            // Stmt Debit (withdrawal) matches Book Credit (payment)
            $isDeposit = (float)$stmt->credit_amount > 0;
            $stmtAmount = $isDeposit ? (float)$stmt->credit_amount : (float)$stmt->debit_amount;

            $candidate = $unmatchedBooks->first(function ($bk) use ($stmt, $stmtAmount, $isDeposit, $usedBookIds) {
                if (in_array($bk->id, $usedBookIds)) return false;

                $bkAmount = $isDeposit ? (float)$bk->debit_amount : (float)$bk->credit_amount;
                if (abs($stmtAmount - $bkAmount) > 0.01) return false;

                // Check date closeness within 7 days
                $stmtDate = Carbon::parse($stmt->transaction_date);
                $bkDate = Carbon::parse($bk->transaction_date);
                return abs($stmtDate->diffInDays($bkDate, false)) <= 7;
            });

            if ($candidate) {
                $usedBookIds[] = $candidate->id;
                $suggestions[] = [
                    'statement_item' => $stmt,
                    'book_item' => $candidate,
                    'confidence' => ($stmt->cheque_reference && $candidate->cheque_reference && str_contains($stmt->cheque_reference, $candidate->cheque_reference)) ? 'High' : 'Medium',
                    'reason' => 'Exact amount ₹' . number_format($stmtAmount, 2) . ' within ' . abs(Carbon::parse($stmt->transaction_date)->diffInDays(Carbon::parse($candidate->transaction_date), false)) . ' days',
                ];
            }
        }

        return $suggestions;
    }
}
