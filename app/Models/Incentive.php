<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incentive extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'staff_id',
        'department_id',
        'target_id',
        'incentive_type',
        'incentive_amount',
        'calculation_basis',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

