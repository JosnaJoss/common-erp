<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'sales_order_id',
        'sales_invoice_id',
        'vehicle_id',
        'route_id',
        'assigned_driver',
        'delivery_number',
        'delivery_date',
        'delivery_address',
        'delivery_status',
        'delivery_remarks',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function items()
    {
        return $this->hasMany(SalesDeliveryItem::class);
    }
}

