<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Weighing extends Model
{
    use SoftDeletes;

    protected $fillable = ['toll_id', 'license_plate', 'driver_dni', 'weight_kg'];
}