<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'service_plan',
        'pppoe_username',
        'ont_serial_number',
        'fiber_node_id',
        'odp_port_number',
        'status',
    ];

    protected $casts = [
        'latitude'        => 'float',
        'longitude'       => 'float',
        'odp_port_number' => 'integer',
    ];

    public function fiberNode()
    {
        return $this->belongsTo(FiberNode::class, 'fiber_node_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'customer_id');
    }
}
