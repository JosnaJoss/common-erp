<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'request_no',
        'request_date',
        'requested_by',
        'priority',
        'expected_delivery_date',
        'remarks',
        'status',
        'approved_by',
        'approval_date',
        'approval_remarks',
    ];

    protected $casts = [
        'request_date' => 'date',
        'expected_delivery_date' => 'date',
        'approval_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

