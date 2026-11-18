<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    protected $fillable = [
        'technician_id',
        'task_id',
        'latitude',
        'longitude',
        'accuracy_m',
        'speed_mps',
        'heading_deg',
        'captured_at',
        'client_uuid',
        'source',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'accuracy_m'  => 'float',
        'speed_mps'   => 'float',
        'heading_deg' => 'float',
        'captured_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
