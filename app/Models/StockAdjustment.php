<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'adjustment_no',
        'adjustment_date',
        'reason',
        'status',
        'approved_by',
        'approval_remarks',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

