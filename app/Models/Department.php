<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'parent_department_id',
        'name',
        'code',
        'department_type',
        'address',
        'postal_code',
        'department_head',
        'head_staff_id',
        'responsible_staff',
        'supervisor_staff_id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function parentDepartment()
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function subDepartments()
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    public function headStaff()
    {
        return $this->belongsTo(Staff::class, 'head_staff_id');
    }

    public function supervisorStaff()
    {
        return $this->belongsTo(Staff::class, 'supervisor_staff_id');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function dailyOperations()
    {
        return $this->hasMany(DailyOperation::class);
    }
}

