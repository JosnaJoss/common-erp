<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'item_id',
        'batch_number',
        'return_quantity',
        'rate',
        'tax_amount',
        'total_amount',
    ];

    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

