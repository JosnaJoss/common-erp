<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'item_id',
        'unit_id',
        'requested_qty',
        'approved_qty',
        'dispatched_qty',
        'received_qty',
        'preferred_batch_id',
        'remarks',
        'issued_qty',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'approved_qty' => 'decimal:3',
        'dispatched_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'issued_qty' => 'decimal:3',
    ];

    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function preferredBatch()
    {
        return $this->belongsTo(StockBatch::class, 'preferred_batch_id');
    }
}

