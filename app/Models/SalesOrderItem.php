<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'item_id',
        'quantity',
        'invoiced_quantity',
        'delivered_quantity',
        'selling_price',
        'tax_percentage',
        'tax_amount',
        'discount_amount',
        'line_total',
    ];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

