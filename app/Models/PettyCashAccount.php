<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'custodian_staff_id',
        'name',
        'code',
        'float_limit',
        'current_balance',
        'ledger_id',
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

    public function custodian()
    {
        return $this->belongsTo(Staff::class, 'custodian_staff_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function vouchers()
    {
        return $this->hasMany(PettyCashVoucher::class);
    }

    public function replenishments()
    {
        return $this->hasMany(PettyCashReplenishment::class);
    }
}
