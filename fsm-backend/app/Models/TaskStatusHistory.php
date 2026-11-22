<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class TaskStatusHistory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'task_id',
        'actor_id',
        'actor_role',
        'from_status',
        'to_status',
        'reason',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
