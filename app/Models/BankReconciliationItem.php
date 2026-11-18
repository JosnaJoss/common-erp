<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_reconciliation_id',
        'journal_line_id',
        'transaction_date',
        'clearance_date',
        'cheque_reference',
        'description',
        'debit_amount',
        'credit_amount',
        'is_cleared',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'clearance_date' => 'date',
        'is_cleared' => 'boolean',
    ];

    public function bankReconciliation()
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalLine()
    {
        return $this->belongsTo(JournalLine::class);
    }
}
