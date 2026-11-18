<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'investor_type',
        'email',
        'phone',
        'pan_number',
        'aadhaar_number',
        'address',
        'shareholding_percentage',
        'total_capital_contributed',
        'total_drawings',
        'current_balance',
        'ledger_id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function investments()
    {
        return $this->hasMany(CapitalInvestment::class);
    }
}
