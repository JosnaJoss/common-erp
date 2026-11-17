<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'operation_number',
        'operation_date',
        'department_head_id',
        'supervisor_id',
        'work_order_ref',
        'process_name',
        'work_description',
        'start_time',
        'end_time',
        'work_status',
        'approval_status',
        'approved_by',
        'approval_date',
        'approval_remarks',
        'rejection_reason',
        'stock_posted',
        'stock_posted_at',
        'total_labor_cost',
        'total_material_cost',
        'total_wastage_cost',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'operation_date' => 'date',
        'approval_date' => 'datetime',
        'stock_posted_at' => 'datetime',
        'stock_posted' => 'boolean',
        'total_labor_cost' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'total_wastage_cost' => 'decimal:2',
    ];

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

    public function departmentHead()
    {
        return $this->belongsTo(Staff::class, 'department_head_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employees()
    {
        return $this->hasMany(DailyOperationEmployee::class);
    }

    public function materials()
    {
        return $this->hasMany(DailyOperationMaterial::class);
    }

    public function outputs()
    {
        return $this->hasMany(DailyOperationOutput::class);
    }

    public function wastages()
    {
        return $this->hasMany(DailyOperationWastage::class);
    }

    public function distributions()
    {
        return $this->hasMany(DailyOperationDistribution::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', 'DailyOperation');
    }

    /**
     * Check if operation can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this->approval_status, ['draft', 'rejected']);
    }

    /**
     * Check if operation is fully approved and stock posted.
     */
    public function isCompleted(): bool
    {
        return in_array($this->approval_status, ['department_head_approved', 'admin_approved']);
    }
}

