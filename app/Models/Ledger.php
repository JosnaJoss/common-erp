<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account_group_id',
        'code',
        'name',
        'opening_balance',
        'balance_type',
        'current_balance',
        'is_bank_or_cash',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_branch',
        'status',
    ];

    protected $casts = [
        'is_bank_or_cash' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * Recalculate balance from journal lines
     */
    public function recalculateBalance(): float
    {
        $debits = $this->journalLines()->sum('debit_amount');
        $credits = $this->journalLines()->sum('credit_amount');
        
        $nature = $this->accountGroup ? $this->accountGroup->nature : 'Assets';
        
        $opening = $this->balance_type === 'debit' ? (float)$this->opening_balance : -(float)$this->opening_balance;
        $net = $opening + ($debits - $credits);

        if (in_array($nature, ['Liabilities', 'Equity', 'Revenue'])) {
            $this->current_balance = abs($net);
            $this->balance_type = $net < 0 ? 'credit' : 'debit';
        } else {
            $this->current_balance = abs($net);
            $this->balance_type = $net >= 0 ? 'debit' : 'credit';
        }

        $this->save();
        return (float) $this->current_balance;
    }
}

