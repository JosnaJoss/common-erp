<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'investor_id',
        'investment_no',
        'transaction_date',
        'transaction_type',
        'amount',
        'payment_mode',
        'deposit_ledger_id',
        'reference_no',
        'remarks',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function depositLedger()
    {
        return $this->belongsTo(Ledger::class, 'deposit_ledger_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
