<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
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

    public function vouchers()
    {
        return $this->hasMany(PettyCashVoucher::class, 'category_id');
    }
}
