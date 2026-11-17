<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperationOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_operation_id',
        'item_id',
        'dest_godown_id',
        'batch_number',
        'produced_quantity',
        'rejected_quantity',
        'wastage_quantity',
        'quality_status',
        'unit_cost',
        'production_date',
        'expiry_date',
        'remarks',
    ];

    protected $casts = [
        'produced_quantity' => 'decimal:3',
        'rejected_quantity' => 'decimal:3',
        'wastage_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }
}

