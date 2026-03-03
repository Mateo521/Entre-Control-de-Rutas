<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toll extends Model
{
    use SoftDeletes;

protected $fillable = [
        'name', 
        'image_path', 
        'dynamic_schema', 
        'dynamic_data'
    ];

    protected $casts = [
        'dynamic_schema' => 'array',
        'dynamic_data' => 'array',
    ];
}