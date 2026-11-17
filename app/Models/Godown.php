<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Godown extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_id',
        'department_id',
        'name',
        'code',
        'location_type',
        'address',
        'postal_code',
        'responsible_staff',
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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function isMobileVan(): bool
    {
        return $this->location_type === 'van_mobile' || !is_null($this->vehicle_id);
    }

    public function isSalesOutlet(): bool
    {
        return $this->location_type === 'sales_outlet';
    }

    public function isProductionUnit(): bool
    {
        return $this->location_type === 'production_unit';
    }
}

