<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class InventoryItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'unit',
        'stock',
        'unit_price',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function taskMaterials()
    {
        return $this->hasMany(TaskMaterial::class);
    }

    public function deductStock(float $qty): bool
    {
        if ($this->stock >= $qty) {
            $this->decrement('stock', $qty);
            return true;
        }
        return false;
    }

    public function restoreStock(float $qty): void
    {
        $this->increment('stock', $qty);
    }
}
