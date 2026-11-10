<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'supplier_id',
        'purchase_order_id',
        'grn_number',
        'received_date',
        'received_by',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }

    public function qualityChecks()
    {
        return $this->hasMany(QualityCheck::class, 'reference_id')->where('reference_type', 'GRN');
    }
}

