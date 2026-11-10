<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'phone',
        'email',
        'shop_name',
        'address',
        'pincode',
        'city',
        'territory',
        'route_id',
        'location_id',
        'gst_number',
        'pan_number',
        'account_group_id',
        'ledger_id',
        'price_level',
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

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function collections()
    {
        return $this->hasMany(CustomerCollection::class);
    }

    /**
     * Get item price for customer according to price tier
     */
    public function getPriceForItem(Item $item): float
    {
        return match ($this->price_level) {
            'minimum' => (float) $item->min_price,
            'maximum' => (float) $item->max_price,
            default => (float) $item->normal_price,
        };
    }
}

