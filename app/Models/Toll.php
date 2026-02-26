<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toll extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'dynamic_schema'];

    protected $casts = [
        'dynamic_schema' => 'array',
    ];
}