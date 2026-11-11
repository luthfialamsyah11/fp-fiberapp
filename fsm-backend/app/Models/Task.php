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

    public function latestProgressUpdate()
    {
        return $this->hasOne(ProgressUpdate::class)->latestOfMany();
    }

    // Alias: proofs (used in web views)
    public function proofs()
    {
        return $this->hasMany(ProofOfWork::class);
    }

    public function latestProof()
    {
        return $this->hasOne(ProofOfWork::class)->latestOfMany();
    }

    // Original relation name kept for compatibility
    public function proofOfWork()
    {
        return $this->hasMany(ProofOfWork::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'accepted', 'on-going']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
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
        if ($this->relationLoaded('progressUpdates')) {
            return $this->progressUpdates->first()?->progress_percent ?? 0;
        }
        return $this->progressUpdates()->first()?->progress_percent ?? 0;
    }
}