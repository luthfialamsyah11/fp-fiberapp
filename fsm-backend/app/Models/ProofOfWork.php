<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProofOfWork extends Model
{
    protected $table = 'proof_of_work';

    protected $fillable = ['task_id', 'image', 'slot', 'description'];

    protected $appends = ['image_url', 'resolved_slot'];

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

        if (\Illuminate\Support\Str::startsWith($this->image, ['http://', 'https://', 'data:image/'])) {
            return $this->image;
        }

        $normalized = ltrim(preg_replace('#^/?storage/#', '', (string) $this->image), '/');
        return url('/storage/' . $normalized);
    }

    public function getResolvedSlotAttribute(): string
    {
        $explicit = strtolower(trim((string) ($this->slot ?? '')));
        if (in_array($explicit, ['before', 'after', 'additional'], true)) {
            return $explicit;
        }

        $desc = strtolower((string) ($this->description ?? ''));
        if (str_contains($desc, 'before') || str_contains($desc, 'sebelum') || str_contains($desc, 'slot 1') || str_contains($desc, 'initial')) {
            return 'before';
        }
        if (str_contains($desc, 'after') || str_contains($desc, 'sesudah') || str_contains($desc, 'slot 2') || str_contains($desc, 'completed')) {
            return 'after';
        }

        return 'additional';
    }
}