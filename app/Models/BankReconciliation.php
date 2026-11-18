<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'ledger_id',
        'reconciliation_no',
        'statement_date',
        'statement_balance',
        'book_balance',
        'reconciled_balance',
        'difference',
        'status',
        'notes',
        'import_filename',
        'reconciled_at',
        'reconciled_by',
        'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'reconciled_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bankLedger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function items()
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
