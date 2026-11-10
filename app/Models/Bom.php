<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'finished_item_id',
        'bom_name',
        'output_quantity',
        'description',
        'status',
    ];

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function items()
    {
        return $this->hasMany(BomItem::class);
    }
}

