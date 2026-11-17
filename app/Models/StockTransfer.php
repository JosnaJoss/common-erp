<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'transfer_no',
        'transfer_date',
        'source_branch_id',
        'source_godown_id',
        'dest_branch_id',
        'dest_godown_id',
        'status',
        'dispatched_by',
        'dispatched_at',
        'received_by',
        'received_at',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function sourceBranch()
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destBranch()
    {
        return $this->belongsTo(Branch::class, 'dest_branch_id');
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}

