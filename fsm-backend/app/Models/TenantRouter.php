<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantRouter extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'driver',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'verify_ssl',
        'isolation_profile',
        'is_default',
        'is_active',
        'last_tested_at',
        'last_test_status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'port'           => 'integer',
        'password'       => 'encrypted',
        'use_ssl'        => 'boolean',
        'verify_ssl'     => 'boolean',
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'router_id');
    }
}
