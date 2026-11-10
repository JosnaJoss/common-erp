<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'payment_type',
        'voucher_id',
        'invoice_type',
        'invoice_id',
        'allocated_amount',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

