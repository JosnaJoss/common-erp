<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'customer_id',
        'sales_order_id',
        'invoice_number',
        'invoice_date',
        'payment_terms',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'stock_updated',
        'journal_entry_id',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'stock_updated' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function collections()
    {
        return $this->hasMany(CustomerCollection::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

