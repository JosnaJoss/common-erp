<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'company_name',
        'phone',
        'email',
        'source',
        'assigned_staff_id',
        'follow_up_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }
}

