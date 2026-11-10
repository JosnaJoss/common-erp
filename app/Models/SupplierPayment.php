<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'purchase_entry_id',
        'payment_number',
        'payment_date',
        'payment_amount',
        'payment_mode',
        'payment_ledger_id',
        'transaction_reference',
        'remarks',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class);
    }

    public function paymentLedger()
    {
        return $this->belongsTo(Ledger::class, 'payment_ledger_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

