<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class FiberNode extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'node_type',
        'parent_id',
        'latitude',
        'longitude',
        'address',
        'total_ports',
        'used_ports',
        'status',
        'notes',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'total_ports' => 'integer',
        'used_ports'  => 'integer',
    ];

    protected $appends = ['available_ports', 'utilization_percent'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'fiber_node_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'fiber_node_id');
    }

    public function getAvailablePortsAttribute(): int
    {
        return max(0, (int) $this->total_ports - (int) $this->used_ports);
    }

    public function getUtilizationPercentAttribute(): int
    {
        $total = (int) $this->total_ports;
        if ($total <= 0) {
            return 0;
        }
        return min(100, (int) round(((int) $this->used_ports / $total) * 100));
    }
}
