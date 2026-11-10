<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'logo',
        'registration_no',
        'gst_no',
        'pan_no',
        'address',
        'phone',
        'email',
        'website',
        'currency',
        'currency_symbol',
        'financial_year_start',
        'financial_year_end',
        'business_type',
        'status',
    ];

    protected $casts = [
        'financial_year_start' => 'date',
        'financial_year_end' => 'date',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function modules()
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function isModuleEnabled(string $key): bool
    {
        $mod = $this->modules->where('module_key', $key)->first();
        return $mod ? (bool)$mod->is_enabled : true; // default true if not configured
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function godowns()
    {
        return $this->hasMany(Godown::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}

