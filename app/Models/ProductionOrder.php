<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'bom_id',
        'finished_item_id',
        'source_godown_id',
        'dest_godown_id',
        'order_number',
        'batch_number',
        'start_date',
        'end_date',
        'planned_quantity',
        'produced_quantity',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function sourceGodown()
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destGodown()
    {
        return $this->belongsTo(Godown::class, 'dest_godown_id');
    }
}

