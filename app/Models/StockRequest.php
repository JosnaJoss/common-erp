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
        'from_department_id',
        'to_department_id',
        'reference_file',
        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function items()
    {
        return $this->hasMany(StockRequestItem::class);
    }
}

