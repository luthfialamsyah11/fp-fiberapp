<?php

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    protected static ?Tenant $tenant = null;
    protected static bool $scopingEnabled = true;

    /**
     * Bind current tenant to request lifecycle.
     */
    public static function setTenant(?Tenant $tenant): void
    {
        static::$tenant = $tenant;
    }

    /**
     * Retrieve currently active tenant model.
     */
    public static function getTenant(): ?Tenant
    {
        return static::$tenant;
    }

    /**
     * Retrieve active tenant ID if present.
     */
    public static function id(): ?int
    {
        return static::$tenant ? (int) static::$tenant->id : null;
    }

    /**
     * Check if a tenant is currently resolved.
     */
    public static function check(): bool
    {
        return static::$tenant !== null;
    }

    /**
     * Check if Eloquent tenant scoping should be applied.
     */
    public static function isScopingEnabled(): bool
    {
        return static::$scopingEnabled && static::check();
    }

    /**
     * Temporarily disable tenant scoping (e.g. for Master Admin or system migrations).
     */
    public static function withoutTenant(callable $callback)
    {
        $previous = static::$scopingEnabled;
        static::$scopingEnabled = false;

        try {
            return $callback();
        } finally {
            static::$scopingEnabled = $previous;
        }
    }

    /**
     * Reset context (useful in tests between requests).
     */
    public static function reset(): void
    {
        static::$tenant = null;
        static::$scopingEnabled = true;
    }
}
