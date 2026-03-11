<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractorWork extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'work_type',
        'location',
        'description',
        'status',
        'media_paths'
    ];

    protected $casts = [
        'media_paths' => 'array',
    ];
}