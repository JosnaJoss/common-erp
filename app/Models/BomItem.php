<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'raw_item_id',
        'quantity',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function rawItem()
    {
        return $this->belongsTo(Item::class, 'raw_item_id');
    }
}

