<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'task_id',
        'customer_id',
        'reference_no',
        'amount',
        'payment_method',
        'provider',
        'qr_payload',
        'qr_image_url',
        'status',
        'expires_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount'     => 'float',
        'expires_at' => 'datetime',
        'paid_at'    => 'datetime',
        'metadata'   => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
