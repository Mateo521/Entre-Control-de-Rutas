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
        'category',  
        'title',
        'description',
        'media_paths'
    ];

    protected $casts = [
        'media_paths' => 'array',
    ];

    public function toll()
    {
        return $this->belongsTo(Toll::class);
    }
}