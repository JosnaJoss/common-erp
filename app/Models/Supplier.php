<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'pincode',
        'city',
        'gst_number',
        'pan_number',
        'account_group_id',
        'ledger_id',
        'opening_balance',
        'opening_balance_date',
        'account_type',
        'current_balance',
        'status',
    ];

    protected $casts = [
        'opening_balance_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseEntries()
    {
        return $this->hasMany(PurchaseEntry::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}

