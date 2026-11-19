<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Technician heartbeat expiry window in seconds (2 minutes = 8 missed 15s heartbeats).
     */
    public const ONLINE_WINDOW_SECONDS = 120;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'is_active', 'is_online', 'last_seen_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_online'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'technician_id');
    }

    public function locations()
    {
        return $this->hasMany(TechnicianLocation::class, 'technician_id');
    }

    public function latestLocation()
    {
        return $this->hasOne(TechnicianLocation::class, 'technician_id')
            ->ofMany(['captured_at' => 'max', 'id' => 'max']);
    }

    public function progressUpdates()
    {
        return $this->hasMany(ProgressUpdate::class);
    }

    public function proofs()
    {
        return $this->hasManyThrough(ProofOfWork::class, Task::class, 'technician_id', 'task_id');
    }

    public function scopeTechnicians($query)
    {
        return $query->where('role', 'technician');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine whether technician is effectively online (has sent a heartbeat or GPS fix within window).
     * Defaults to ONLINE_WINDOW_SECONDS (120 seconds / 2 minutes).
     */
    public function isEffectivelyOnline(?int $windowSeconds = null): bool
    {
        if (!$this->is_online) {
            return false;
        }

        $seconds = $windowSeconds ?? self::ONLINE_WINDOW_SECONDS;
        $cutoff = now()->subSeconds($seconds);

        if ($this->last_seen_at && $this->last_seen_at->gt($cutoff)) {
            return true;
        }

        $latest = $this->relationLoaded('latestLocation') ? $this->latestLocation : $this->latestLocation()->first();
        $latestTimestamp = $latest ? ($latest->captured_at ?? $latest->created_at) : null;
        if ($latestTimestamp && $latestTimestamp->gt($cutoff)) {
            return true;
        }

        // If neither timestamp exists yet (e.g. freshly seeded user), fall back to updated_at within window
        if (!$this->last_seen_at && !$latest && $this->updated_at && $this->updated_at->gt($cutoff)) {
            return true;
        }

        return false;
    }
}
