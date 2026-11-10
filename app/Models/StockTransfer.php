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
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}

