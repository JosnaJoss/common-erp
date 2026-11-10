<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'city_id',
        'name',
        'pincode',
        'status',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

