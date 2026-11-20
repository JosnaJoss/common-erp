<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'staff_id',
        'salary_month',
        'total_days',
        'present_days',
        'absent_days',
        'basic_salary',
        'hra',
        'other_allowances',
        'incentives',
        'overtime_amount',
        'pf_deduction',
        'esi_deduction',
        'other_deductions',
        'lop_deduction',
        'advance_deduction',
        'loan_deduction',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'manufacturing_labor_settled',
        'administrative_salary_expense',
        'payment_date',
        'payment_mode',
        'transaction_reference',
        'payroll_status',
        'journal_entry_id',
        'processed_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'manufacturing_labor_settled' => 'decimal:2',
        'administrative_salary_expense' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

