<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title', 'customer_name', 'customer_phone', 'description', 'address', 'location',
        'latitude', 'longitude', 'technician_id', 'status', 'priority',
        'additional_notes', 'scheduled_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = [
        'resolved_location',
        'status_label',
        'progress_percent'
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function progressUpdates()
    {
        return $this->hasMany(ProgressUpdate::class)->orderBy('created_at', 'desc');
    }

    // Alias: proofs (used in web views)
    public function proofs()
    {
        return $this->hasMany(ProofOfWork::class);
    }

    // Original relation name kept for compatibility
    public function proofOfWork()
    {
        return $this->hasMany(ProofOfWork::class);
    }

    /**
     * Get the resolved location (uses 'location' column first, falls back to 'address').
     */
    public function getResolvedLocationAttribute(): string
    {
        return $this->location ?? $this->address ?? '-';
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'Menunggu',
            'assigned'    => 'Ditugaskan',
            'accepted'    => 'Diterima',
            'on-going'    => 'On-Going',
            'completed'   => 'Selesai',
            'rejected'    => 'Ditolak',
            default       => ucfirst($this->status),
        };
    }

    /**
     * Get the current progress percentage based on the latest update.
     */
    public function getProgressPercentAttribute(): int
    {
        return $this->progressUpdates()->first()?->progress_percent ?? 0;
    }
}