<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TowService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tow_truck',
        'license_plate',
        'location',
        'reason',
        'observations'
    ];
}