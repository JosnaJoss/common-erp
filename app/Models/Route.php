<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_id',
        'route_name',
        'assigned_driver',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'route_cities');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'route_locations');
    }
}

