<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperationEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_operation_id',
        'staff_id',
        'employment_type',
        'hours_worked',
        'units_completed',
        'rate_per_unit',
        'wage_earned',
        'remarks',
    ];

    protected $casts = [
        'hours_worked' => 'decimal:2',
        'units_completed' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'wage_earned' => 'decimal:2',
    ];

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

