<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'godown_id',
        'item_id',
        'batch_no',
        'batch_name',
        'quantity',
        'cost_price',
        'added_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'added_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function godown()
    {
        return $this->belongsTo(Godown::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

