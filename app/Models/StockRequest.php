<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'request_no',
        'movement_type',
        'request_date',
        'required_date',
        'priority',
        'from_department_id',
        'to_department_id',
        'from_godown_id',
        'source_godown_id',
        'reference_file',
        'remarks',
        'rejection_reason',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromGodown()
    {
        return $this->belongsTo(Godown::class, 'from_godown_id');
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(StockRequestItem::class);
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class);
    }
}

