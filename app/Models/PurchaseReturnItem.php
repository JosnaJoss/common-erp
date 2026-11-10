<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'item_id',
        'batch_number',
        'return_quantity',
        'rate',
        'tax_amount',
        'total_amount',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

