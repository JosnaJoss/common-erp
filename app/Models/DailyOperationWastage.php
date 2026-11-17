<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperationWastage extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_operation_id',
        'item_id',
        'wastage_name',
        'wastage_type',
        'quantity',
        'unit',
        'estimated_cost',
        'recoverable_quantity',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'estimated_cost' => 'decimal:2',
        'recoverable_quantity' => 'decimal:3',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

