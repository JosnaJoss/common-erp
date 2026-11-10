<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_name',
        'vehicle_type',
        'registration_number',
        'capacity',
        'assigned_driver',
        'insurance_expiry_date',
        'fitness_certificate_expiry_date',
        'status',
    ];

    protected $casts = [
        'insurance_expiry_date' => 'date',
        'fitness_certificate_expiry_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

