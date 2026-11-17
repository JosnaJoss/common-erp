<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperationDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_operation_id',
        'item_id',
        'destination_godown_id',
        'destination_type',
        'batch_no',
        'quantity',
        'stock_transfer_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function destinationGodown()
    {
        return $this->belongsTo(Godown::class, 'destination_godown_id');
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }
}

