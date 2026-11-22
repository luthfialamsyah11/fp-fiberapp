<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class NotificationLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'task_id',
        'channel',
        'recipient',
        'event',
        'payload',
        'status',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'payload'  => 'array',
        'sent_at'  => 'datetime',
        'attempts' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
