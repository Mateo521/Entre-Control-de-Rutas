<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

  
    protected $fillable = [
        'toll_id',
        'user_id',
        'incident_type',
        'dynamic_data',
        'media_paths',
        'status',
    ];

    
    protected function casts(): array
    {
        return [
            'dynamic_data' => 'array',
            'media_paths' => 'array',
        ];
    }

    public function toll()
    {
        return $this->belongsTo(Toll::class);
    }

}