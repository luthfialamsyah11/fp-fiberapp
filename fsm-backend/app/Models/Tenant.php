<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'contact_name',
        'contact_email',
        'contact_phone',
        'plan',
        'status',
        'max_technicians',
        'max_fiber_nodes',
        'trial_ends_at',
        'subscription_ends_at',
        'brand_logo_url',
        'brand_color',
        'settings',
        'network_settings',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
        'settings'             => 'array',
        'network_settings'     => 'array',
        'max_technicians'      => 'integer',
        'max_fiber_nodes'      => 'integer',
    ];

    /**
     * Users belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Tasks belonging to this tenant.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Customers belonging to this tenant.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Fiber nodes (OLT/ODC/ODP) belonging to this tenant.
     */
    public function fiberNodes(): HasMany
    {
        return $this->hasMany(FiberNode::class);
    }

    /**
     * Inventory items belonging to this tenant.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Check if tenant has an active trial or paid subscription.
     */
    public function isActive(): bool
    {
        if ($this->status === 'suspended') {
            return false;
        }

        if ($this->status === 'active') {
            return true;
        }

        if ($this->status === 'trial') {
            return $this->trial_ends_at ? $this->trial_ends_at->isFuture() : true;
        }

        return false;
    }

    /**
     * Check if tenant is allowed to add more technicians under their current plan.
     */
    public function canAddTechnician(): bool
    {
        $currentCount = $this->users()->where('role', 'technician')->count();
        return $currentCount < $this->max_technicians;
    }

    /**
     * Check if tenant is allowed to add more fiber nodes (ODP/ODC) under their current plan.
     */
    public function canAddFiberNode(): bool
    {
        $currentCount = $this->fiberNodes()->count();
        return $currentCount < $this->max_fiber_nodes;
    }
}
