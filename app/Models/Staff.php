<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'aadhaar_number',
        'pan_number',
        'date_of_birth',
        'gender',
        'blood_group',
        'highest_education',
        'marital_status',
        'photo',
        'permanent_address',
        'temporary_address',
        'emergency_contact_person',
        'emergency_contact_relation',
        'emergency_contact_number',
        'designation',
        'employee_type',
        'salary_amount',
        'date_of_joining',
        'last_working_date',
        'status',
        'basic_salary',
        'hra',
        'other_allowances',
        'standard_deductions',
        'pf_applicable',
        'pf_number',
        'pf_uan',
        'pf_rate_percent',
        'esi_applicable',
        'esi_number',
        'esi_rate_percent',
        'other_benefits',
        'license_number',
        'license_class',
        'license_start_date',
        'license_expiry_date',
        'license_document',
        'bank_name',
        'bank_ifsc',
        'bank_branch',
        'bank_account_type',
        'bank_holder_name',
        'bank_account_number',
        'bank_passbook_file',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'last_working_date' => 'date',
        'license_start_date' => 'date',
        'license_expiry_date' => 'date',
        'pf_applicable' => 'boolean',
        'esi_applicable' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function targets()
    {
        return $this->hasMany(StaffTarget::class);
    }

    public function allowances()
    {
        return $this->hasMany(StaffAllowance::class);
    }
}

