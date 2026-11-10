<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_delivery_id',
        'item_id',
        'batch_number',
        'quantity',
    ];

    public function delivery()
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

