<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'stock_request_item_id',
        'item_id',
        'batch_id',
        'quantity',
        'dispatched_qty',
        'received_qty',
        'damaged_qty',
        'shortage_qty',
        'in_transit_qty',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'dispatched_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'damaged_qty' => 'decimal:3',
        'shortage_qty' => 'decimal:3',
        'in_transit_qty' => 'decimal:3',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function requestItem()
    {
        return $this->belongsTo(StockRequestItem::class, 'stock_request_item_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function discrepancies()
    {
        return $this->hasMany(StockDiscrepancy::class, 'stock_transfer_item_id');
    }
}

