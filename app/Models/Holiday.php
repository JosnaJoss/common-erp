<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'holiday_name',
        'holiday_date',
        'holiday_type',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

