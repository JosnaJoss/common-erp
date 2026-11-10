<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_entry_id',
        'item_id',
        'unit_id',
        'batch_number',
        'expiry_date',
        'weight',
        'quantity',
        'rate',
        'tax_percentage',
        'tax_amount',
        'discount_amount',
        'line_total',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}

