<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class Task extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'customer_name', 'customer_phone', 'customer_email', 'description', 'address', 'location',
        'latitude', 'longitude', 'technician_id', 'customer_id', 'fiber_node_id', 'status', 'priority',
        'source', 'service_category', 'client_photo',
        'additional_notes', 'scheduled_at',
        'tracking_token', 'customer_signature', 'customer_signer_name', 'customer_signed_at',
        'completed_at', 'bast_hash', 'bast_sealed_at',
        'optical_power_rx_dbm', 'optical_power_tx_dbm', 'hardware_verified_at',
        'billing_amount', 'payment_status',
        'customer_rating', 'customer_feedback', 'customer_rated_at', 'sla_escalated_at'
    ];

    protected $casts = [
        'scheduled_at'          => 'datetime',
        'customer_signed_at'    => 'datetime',
        'completed_at'          => 'datetime',
        'bast_sealed_at'        => 'datetime',
        'hardware_verified_at'  => 'datetime',
        'customer_rated_at'     => 'datetime',
        'sla_escalated_at'      => 'datetime',
        'customer_rating'       => 'integer',
        'optical_power_rx_dbm'  => 'float',
        'optical_power_tx_dbm'  => 'float',
        'billing_amount'        => 'float',
        'latitude'              => 'float',
        'longitude'             => 'float',
    ];

    protected $appends = [
        'resolved_location',
        'status_label',
        'progress_percent',
        'tracking_url',
        'customer_signature_url',
        'client_photo_url',
        'service_category_label',
    ];

    protected static function booted()
    {
        static::creating(function ($task) {
            if (empty($task->tracking_token)) {
                $task->tracking_token = Str::random(32);
            }
        });
    }

    public function ensureTrackingToken(): string
    {
        if (empty($this->tracking_token)) {
            $this->update(['tracking_token' => Str::random(32)]);
        }
        return $this->tracking_token;
    }

    public function getTrackingUrlAttribute(): string
    {
        $token = $this->tracking_token ?: '';
        return $token ? url('/track/' . $token) : '';
    }

    public function getCustomerSignatureUrlAttribute(): ?string
    {
        if (empty($this->customer_signature)) {
            return null;
        }
        $sig = (string) $this->customer_signature;
        if (Str::startsWith($sig, ['http://', 'https://', 'data:image/'])) {
            return $sig;
        }
        $normalized = ltrim(preg_replace('#^/?storage/#', '', $sig), '/');
        return url('/storage/' . $normalized);
    }

    public function getClientPhotoUrlAttribute(): ?string
    {
        if (empty($this->client_photo)) {
            return null;
        }
        $photo = (string) $this->client_photo;
        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return $photo;
        }
        $normalized = ltrim(preg_replace('#^/?storage/#', '', $photo), '/');
        return url('/storage/' . $normalized);
    }

    public function getServiceCategoryLabelAttribute(): string
    {
        return match ((string) $this->service_category) {
            'new_install' => 'New Fiber Installation',
            'outage_los'  => 'Total Outage / LOS Red Light',
            'slow_speed'  => 'Slow Speed / High Optical Loss',
            'relocation'  => 'Relocation / Maintenance',
            'other'       => 'General Technical Support',
            default       => $this->source === 'client_portal' ? 'Client Service Request' : 'NOC Work Order',
        };
    }

    public function scopeClientPortal($query)
    {
        return $query->where('source', 'client_portal');
    }

    public function isSlaBreached(?int $slaHours = null): bool
    {
        if (in_array($this->status, ['completed', 'rejected'], true)) {
            return false;
        }
        $hours = $slaHours ?? (int) \App\Services\SettingService::get('sla_warning_hours', '2');
        $referenceTime = $this->scheduled_at ?? $this->created_at;
        if ($hours <= 0 || !$referenceTime) {
            return false;
        }
        return $referenceTime->lt(now()->subHours($hours));
    }

    public function scopeSlaBreached($query, ?int $slaHours = null)
    {
        $hours = $slaHours ?? (int) \App\Services\SettingService::get('sla_warning_hours', '2');
        if ($hours <= 0) {
            return $query->whereRaw('1 = 0');
        }
        $cutoff = now()->subHours($hours);
        return $query->whereIn('status', ['pending', 'assigned', 'accepted', 'on-going'])
            ->where(function ($sub) use ($cutoff) {
                $sub->whereNotNull('scheduled_at')->where('scheduled_at', '<=', $cutoff)
                    ->orWhere(function ($fb) use ($cutoff) {
                        $fb->whereNull('scheduled_at')->where('created_at', '<=', $cutoff);
                    });
            });
    }

    public function isBastIntegrityValid(): bool
    {
        if ($this->status !== 'completed' || empty($this->bast_hash)) {
            return false;
        }
        return hash_equals((string) $this->bast_hash, $this->computeBastHash());
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function fiberNode()
    {
        return $this->belongsTo(FiberNode::class, 'fiber_node_id');
    }

    public function progressUpdates()
    {
        return $this->hasMany(ProgressUpdate::class)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function latestProgressUpdate()
    {
        return $this->hasOne(ProgressUpdate::class)->latestOfMany();
    }

    public function statusHistories()
    {
        return $this->hasMany(TaskStatusHistory::class)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function materials()
    {
        return $this->hasMany(TaskMaterial::class)->with('inventoryItem');
    }

    public function proofs()
    {
        return $this->hasMany(ProofOfWork::class)->orderBy('id', 'asc');
    }

    public function latestProof()
    {
        return $this->hasOne(ProofOfWork::class)->latestOfMany();
    }

    public function proofOfWork()
    {
        return $this->hasMany(ProofOfWork::class)->orderBy('id', 'asc');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'task_id')->orderByDesc('id');
    }

    public function latestPayment()
    {
        return $this->hasOne(PaymentTransaction::class, 'task_id')->latestOfMany();
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

    public function getResolvedLocationAttribute(): string
    {
        return $this->location ?? $this->address ?? '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'Pending',
            'assigned'    => 'Assigned',
            'accepted'    => 'Accepted',
            'on-going'    => 'In Progress',
            'completed'   => 'Completed',
            'rejected'    => 'Declined',
            default       => ucfirst((string) $this->status),
        };
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }
        if ($this->relationLoaded('progressUpdates')) {
            return $this->progressUpdates->first()?->progress_percent ?? 0;
        }
        if ($this->relationLoaded('latestProgressUpdate')) {
            return $this->latestProgressUpdate?->progress_percent ?? 0;
        }
        return $this->progressUpdates()->value('progress_percent') ?? 0;
    }

    /**
     * Compute deterministic SHA-256 cryptographic seal over task acceptance payload.
     */
    public function computeBastHash(): string
    {
        $this->loadMissing(['materials', 'proofOfWork']);

        $materialsCanonical = $this->materials
            ->sortBy('id')
            ->map(fn ($m) => [
                'item_id' => (int) $m->inventory_item_id,
                'qty'     => (float) $m->quantity,
                'sn'      => (string) ($m->serial_number ?? ''),
            ])
            ->values()
            ->all();

        $proofsCanonical = $this->proofOfWork
            ->sortBy('id')
            ->map(fn ($p) => [
                'image' => (string) $p->image,
                'desc'  => (string) ($p->description ?? ''),
            ])
            ->values()
            ->all();

        $payload = [
            'task_id'         => (int) $this->id,
            'customer_name'   => (string) $this->customer_name,
            'technician_id'   => (int) ($this->technician_id ?? 0),
            'signer_name'     => (string) ($this->customer_signer_name ?? ''),
            'signature_ref'   => (string) ($this->customer_signature ?? ''),
            'materials'       => $materialsCanonical,
            'proofs'          => $proofsCanonical,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
