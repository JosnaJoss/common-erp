<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'subcategory_id',
        'product_id',
        'unit_id',
        'name',
        'code',
        'item_type',
        'weight',
        'min_price',
        'normal_price',
        'max_price',
        'gst_percentage',
        'hsn_code',
        'image',
        'reorder_quantity',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function totalStockQuantity(): float
    {
        return (float) $this->batches()->sum('quantity');
    }
}

