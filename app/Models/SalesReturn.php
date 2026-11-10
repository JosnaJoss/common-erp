<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'customer_id',
        'sales_invoice_id',
        'return_number',
        'return_date',
        'total_amount',
        'return_reason',
        'stock_condition',
        'status',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}

