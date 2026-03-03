<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['name', 'category', 'unit_measure', 'current_stock', 'alert_threshold'];

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