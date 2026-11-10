<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'staff_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'attendance_status',
        'working_hours',
        'overtime_hours',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

