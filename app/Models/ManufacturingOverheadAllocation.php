<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingOverheadAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'daily_operation_id',
        'expense_ledger_id',
        'overhead_category',
        'allocation_method',
        'allocation_basis_value',
        'rate_per_unit',
        'allocated_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'allocation_basis_value' => 'decimal:4',
        'rate_per_unit' => 'decimal:4',
        'allocated_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function dailyOperation()
    {
        return $this->belongsTo(DailyOperation::class);
    }

    public function expenseLedger()
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
