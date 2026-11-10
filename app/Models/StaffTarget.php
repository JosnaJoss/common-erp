<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'staff_id',
        'target_name',
        'target_amount',
        'start_date',
        'end_date',
        'target_frequency',
        'description',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

