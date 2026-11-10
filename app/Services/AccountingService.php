<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
    /**
     * Post a balanced journal entry
     *
     * @param int $companyId
     * @param int|null $branchId
     * @param string $voucherType
     * @param string $voucherDate
     * @param string|null $narration
     * @param array $lines  Array of ['ledger_id' => int, 'debit' => float, 'credit' => float, 'narration' => string|null]
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $referenceNo
     * @return JournalEntry
     * @throws Exception
     */
    public function createJournalEntry(
        int $companyId,
        ?int $branchId,
        string $voucherType,
        string $voucherDate,
        ?string $narration,
        array $lines,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNo = null
    ): JournalEntry {
        return DB::transaction(function () use (
            $companyId, $branchId, $voucherType, $voucherDate, $narration, $lines, $referenceType, $referenceId, $referenceNo
        ) {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }

            // Verify balanced double entry (tolerance 0.01)
            if (abs($totalDebit - $totalCredit) > 0.05) {
                throw new Exception("Accounting Error: Debits (" . number_format($totalDebit, 2) . ") must equal Credits (" . number_format($totalCredit, 2) . ")");
            }

            // Find or link active financial year
            $financialYear = FinancialYear::where('company_id', $companyId)
                ->where('start_date', '<=', $voucherDate)
                ->where('end_date', '>=', $voucherDate)
                ->where('is_closed', false)
                ->first();

            $voucherNo = $this->generateVoucherNumber($companyId, $voucherType);

            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'financial_year_id' => $financialYear?->id,
                'voucher_no' => $voucherNo,
                'voucher_type' => $voucherType,
                'voucher_date' => $voucherDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'narration' => $narration,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_posted' => true,
                'is_reversed' => false,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $debit = (float)($line['debit'] ?? 0);
                $credit = (float)($line['credit'] ?? 0);
                if ($debit == 0 && $credit == 0) {
                    continue;
                }

                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'ledger_id' => $line['ledger_id'],
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'narration' => $line['narration'] ?? $narration,
                ]);

                // Recalculate ledger balance
                $ledger = Ledger::find($line['ledger_id']);
                if ($ledger) {
                    $ledger->recalculateBalance();
                }
            }

            return $entry;
        });
    }

    /**
     * Generate unique voucher number
     */
    public function generateVoucherNumber(int $companyId, string $type): string
    {
        $prefix = match ($type) {
            'Payment' => 'PAY',
            'Receipt' => 'REC',
            'Contra' => 'CON',
            'Purchase' => 'PUR',
            'Sales' => 'SAL',
            'Purchase Return' => 'PRT',
            'Sales Return' => 'SRT',
            'Payroll' => 'PAYR',
            default => 'JV',
        };

        $year = date('Y');
        $count = JournalEntry::where('company_id', $companyId)
            ->where('voucher_type', $type)
            ->whereYear('voucher_date', $year)
            ->count() + 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $count);
    }

    /**
     * Get or create standard system ledgers for a company
     */
    public function getOrCreateStandardLedger(int $companyId, string $ledgerName, string $groupName, string $nature = 'Assets'): Ledger
    {
        $group = \App\Models\AccountGroup::firstOrCreate(
            ['company_id' => $companyId, 'name' => $groupName],
            ['nature' => $nature]
        );

        return Ledger::firstOrCreate(
            ['company_id' => $companyId, 'name' => $ledgerName],
            [
                'account_group_id' => $group->id,
                'balance_type' => in_array($nature, ['Assets', 'Expenses']) ? 'debit' : 'credit',
                'current_balance' => 0,
                'is_bank_or_cash' => in_array($ledgerName, ['Cash in Hand', 'Petty Cash', 'Main Bank Account']),
            ]
        );
    }
}

