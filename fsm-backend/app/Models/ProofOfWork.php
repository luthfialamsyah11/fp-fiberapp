<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProofOfWork extends Model
{
    protected $table = 'proof_of_work';

    protected $fillable = ['task_id', 'image', 'description'];

    protected $appends = ['image_url'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function technician()
    {
        return $this->hasOneThrough(User::class, Task::class, 'id', 'id', 'task_id', 'technician_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return asset('images/no-image.png');
        }

        if (\Illuminate\Support\Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }
}