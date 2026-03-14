<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'category', 
        'unit_measure', 
        'alert_threshold', 
        'current_stock'
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_inventory')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }
}