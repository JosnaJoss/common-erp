<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryProcessTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'industry_type',
        'department_name',
        'process_name',
        'process_code',
        'description',
        'default_uom',
        'requires_qc',
        'display_order',
        'status',
    ];

    protected $casts = [
        'requires_qc' => 'boolean',
        'display_order' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

