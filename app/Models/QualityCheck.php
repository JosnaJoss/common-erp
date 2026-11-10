<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'department_id',
        'item_id',
        'inspection_number',
        'reference_type',
        'reference_id',
        'reference_number',
        'inspection_date',
        'weight_before_testing',
        'unit_before_testing',
        'weight_after_testing',
        'unit_after_testing',
        'quality_test_details',
        'result',
        'accepted_quantity',
        'rejected_quantity',
        'remarks',
        'checked_by',
        'approval_status',
        'approved_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

