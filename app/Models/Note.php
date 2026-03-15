<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'is_completed',
        'is_archived'
    ];

    
    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'is_archived' => 'boolean',
    ];
}