<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    // Habilita el archivado lógico en lugar de borrado físico
    use SoftDeletes; 

    // Define qué columnas se pueden insertar mediante un array
    protected $fillable = [
        'toll_id',
        'user_id',
        'incident_type',
        'dynamic_data',
        'media_paths',
    ];

    // Transforma automáticamente los campos JSON de PostgreSQL a Arrays en PHP
    protected $casts = [
        'dynamic_data' => 'array',
        'media_paths' => 'array',
    ];

    // Relación: Un suceso pertenece a un Peaje
    public function toll(): BelongsTo
    {
        return $this->belongsTo(Toll::class);
    }

    // Relación: Un suceso es reportado por un Usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}