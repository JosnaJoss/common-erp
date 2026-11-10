<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'contact_number',
        'email',
        'branch_manager',
        'is_main_branch',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function godowns()
    {
        return $this->hasMany(Godown::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

