<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'staff_id',
        'advance_no',
        'advance_date',
        'amount',
        'payment_mode',
        'ledger_id',
        'reference_no',
        'reason',
        'status',
        'recovered_amount',
        'balance_amount',
        'journal_entry_id',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'amount' => 'decimal:2',
        'recovered_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
