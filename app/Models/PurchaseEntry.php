<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'supplier_id',
        'purchase_order_id',
        'goods_receipt_note_id',
        'purchase_number',
        'invoice_number',
        'invoice_date',
        'invoice_file',
        'payment_type',
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
        'stock_updated' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseEntryItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

