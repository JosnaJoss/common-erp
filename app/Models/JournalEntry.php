<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'voucher_no',
        'voucher_type',
        'voucher_date',
        'reference_type',
        'reference_id',
        'reference_no',
        'narration',
        'total_debit',
        'total_credit',
        'is_posted',
        'is_reversed',
        'reversed_by_id',
        'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'is_posted' => 'boolean',
        'is_reversed' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

