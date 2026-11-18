<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'petty_cash_account_id',
        'voucher_no',
        'voucher_date',
        'expense_category',
        'amount',
        'paid_to',
        'receipt_doc',
        'remarks',
        'status',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(PettyCashAccount::class, 'petty_cash_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
