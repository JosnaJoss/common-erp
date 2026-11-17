<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperationMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_operation_id',
        'item_id',
        'source_godown_id',
        'batch_no',
        'opening_quantity',
        'issued_quantity',
        'consumed_quantity',
        'returned_quantity',
        'wastage_quantity',
        'remaining_quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'opening_quantity' => 'decimal:3',
        'issued_quantity' => 'decimal:3',
        'consumed_quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
        'wastage_quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }
}

