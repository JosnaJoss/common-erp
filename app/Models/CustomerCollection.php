<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'sales_invoice_id',
        'collection_number',
        'collection_date',
        'collection_amount',
        'payment_mode',
        'receipt_ledger_id',
        'transaction_reference',
        'collected_by',
        'remarks',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'collection_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function receiptLedger()
    {
        return $this->belongsTo(Ledger::class, 'receipt_ledger_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

