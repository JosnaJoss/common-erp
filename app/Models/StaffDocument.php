<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'document_type',
        'title',
        'file_path',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

