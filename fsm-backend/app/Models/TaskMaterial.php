<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'inventory_item_id',
        'quantity',
        'serial_number',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
