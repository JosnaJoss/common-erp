<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockDiscrepancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'stock_transfer_id',
        'stock_transfer_item_id',
        'stock_request_id',
        'item_id',
        'batch_id',
        'source_godown_id',
        'dest_godown_id',
        'dispatched_qty',
        'received_qty',
        'discrepancy_qty',
        'discrepancy_type',
        'reason',
        'remarks',
        'status',
        'investigation_remarks',
        'investigated_by',
        'investigated_at',
        'resolution_type',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'stock_adjustment_id',
        'created_by',
    ];

    protected $casts = [
        'dispatched_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'discrepancy_qty' => 'decimal:3',
        'investigated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function transferItem()
    {
        return $this->belongsTo(StockTransferItem::class, 'stock_transfer_item_id');
    }

    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }

    public function investigator()
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function approvals()
    {
        return $this->hasMany(StockDiscrepancyApproval::class);
    }
}
