<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    /**
     * Boot the trait and register the global TenantScope.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (!app()->runningInConsole() || Schema::hasColumn($model->getTable(), 'tenant_id')) {
                    $model->tenant_id = TenantContext::id() ?? 1;
                }
            }
        });
    }

    /**
     * The tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
