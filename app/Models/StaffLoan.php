<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'staff_id',
        'loan_no',
        'loan_date',
        'principal_amount',
        'interest_rate',
        'repayment_months',
        'monthly_emi',
        'start_month',
        'disbursed_ledger_id',
        'outstanding_balance',
        'status',
        'journal_entry_id',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_emi' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function disbursedLedger()
    {
        return $this->belongsTo(Ledger::class, 'disbursed_ledger_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function installments()
    {
        return $this->hasMany(StaffLoanInstallment::class);
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
