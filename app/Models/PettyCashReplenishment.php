<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashReplenishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'petty_cash_account_id',
        'replenishment_no',
        'replenishment_date',
        'amount',
        'bank_ledger_id',
        'transaction_reference',
        'notes',
        'status',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'replenishment_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(PettyCashAccount::class, 'petty_cash_account_id');
    }

    public function bankLedger()
    {
        return $this->belongsTo(Ledger::class, 'bank_ledger_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
