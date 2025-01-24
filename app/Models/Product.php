<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded;

    protected $casts = [
        'images' => 'array',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}
