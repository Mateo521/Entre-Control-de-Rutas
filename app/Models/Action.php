<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Action extends Model
{
    use HasFactory, SoftDeletes;

  protected $fillable = [
        'toll_id',
        'category', // <--- NUEVA COLUMNA
        'title',
        'description',
        'media_paths'
    ];

    // Forzamos a Laravel a transformar el JSON de la BD en un Array de PHP automáticamente
    protected $casts = [
        'media_paths' => 'array',
    ];

    public function toll()
    {
        return $this->belongsTo(Toll::class);
    }
}