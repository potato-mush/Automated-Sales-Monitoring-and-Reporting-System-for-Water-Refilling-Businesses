<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'category',
        'quantity',
        'unit',
        'unit_price',
        'reorder_level',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'reorder_level' => 'integer',
    ];

    // Check if item needs reordering
    public function needsReorder()
    {
        return $this->quantity <= $this->reorder_level;
    }

    // Get total value of this inventory item
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    // Scope for low stock items
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= reorder_level');
    }

    // Scope by category
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
