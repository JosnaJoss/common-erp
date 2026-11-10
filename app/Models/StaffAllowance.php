<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAllowance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'staff_id',
        'allowance_type',
        'amount',
        'frequency',
        'effective_date',
        'remarks',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

