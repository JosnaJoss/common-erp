<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'shift_name',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

