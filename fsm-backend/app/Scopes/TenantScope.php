<?php

namespace App\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isScopingEnabled()) {
            if (app()->runningInConsole() && !Schema::hasColumn($model->getTable(), 'tenant_id')) {
                return;
            }
            $builder->where($model->qualifyColumn('tenant_id'), TenantContext::id());
        }
    }
}
