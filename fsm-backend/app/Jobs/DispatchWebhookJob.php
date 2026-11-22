<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 120];

    public int $timeout = 15;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $event,
        public readonly array $payload,
        public readonly ?int $taskId = null
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        $previousTenant = TenantContext::getTenant();
        TenantContext::setTenant($tenant);

        try {
            $sent = SettingService::dispatchWebhook($this->event, $this->payload);

            if (Schema::hasTable('notification_logs')) {
                $webhookUrl = trim((string) SettingService::get('webhook_url', ''));
                if ($webhookUrl !== '') {
                    NotificationLog::create([
                        'tenant_id' => $this->tenantId,
                        'task_id'   => $this->taskId,
                        'channel'   => 'webhook',
                        'event'     => $this->event,
                        'recipient' => substr($webhookUrl, 0, 250),
                        'payload'   => json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'status'    => $sent ? 'sent' : 'failed',
                        'sent_at'   => $sent ? now() : null,
                    ]);
                }
            }
        } finally {
            TenantContext::setTenant($previousTenant);
        }
    }
}
