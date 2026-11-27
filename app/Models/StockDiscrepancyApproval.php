<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockDiscrepancyApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_discrepancy_id',
        'approver_id',
        'action',
        'decision_remarks',
        'quantity',
        'estimated_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'estimated_value' => 'decimal:2',
    ];

    public function discrepancy()
    {
        return $this->belongsTo(StockDiscrepancy::class, 'stock_discrepancy_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
