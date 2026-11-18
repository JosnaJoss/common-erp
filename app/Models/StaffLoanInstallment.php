<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_loan_id',
        'payroll_id',
        'installment_number',
        'due_month',
        'amount',
        'status',
        'deducted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deducted_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(StaffLoan::class, 'staff_loan_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
