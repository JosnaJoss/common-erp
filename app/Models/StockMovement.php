<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'item_id',
        'batch_id',
        'movement_date',
        'movement_type',
        'reference_type',
        'reference_id',
        'reference_no',
        'quantity',
        'unit_cost',
        'balance_after',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}

