<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'supplier_id',
        'purchase_entry_id',
        'return_number',
        'return_date',
        'total_amount',
        'return_reason',
        'status',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}

