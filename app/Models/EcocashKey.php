<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcocashKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'integration_key',
        'return_url',
        'result_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
