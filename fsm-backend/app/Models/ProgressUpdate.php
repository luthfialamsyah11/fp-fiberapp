<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressUpdate extends Model
{
    protected $fillable = ['task_id', 'user_id', 'note', 'progress_percent'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}