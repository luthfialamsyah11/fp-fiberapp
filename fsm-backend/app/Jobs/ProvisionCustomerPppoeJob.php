<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Tenant;
use App\Services\Network\MikrotikService;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionCustomerPppoeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 120];

    public int $timeout = 15;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId
    ) {
        $this->onQueue('hardware');
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $previousTenant = TenantContext::getTenant();
        TenantContext::setTenant($tenant);

        try {
            $customer = Customer::withoutGlobalScopes()->find($this->customerId);
            if (!$customer) {
                return;
            }

            MikrotikService::forTenant($tenant)->provisionCustomerPppoe($customer);
            Log::info("[ProvisionCustomerPppoeJob] PPPoE provisioned asynchronously for CID {$customer->customer_code}");
        } finally {
            TenantContext::setTenant($previousTenant);
        }
    }
}
