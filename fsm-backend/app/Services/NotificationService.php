<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\NotificationLog;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Non-blocking Multi-Channel Notification Engine (Rumahweb cPanel compatible).
 * Uses dispatch()->afterResponse() so HTTP requests never stall on external gateways,
 * while persisting an immutable audit ledger in notification_logs for retryable delivery.
 */
class NotificationService
{
    public function notifyTaskAssigned(Task $task): void
    {
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $tech = $task->technician;

        // 1. Native Push (FCM) to assigned technician's registered mobile devices
        if ($tech) {
            $this->enqueuePushToUser(
                $tech->id,
                $task->id,
                'TASK_ASSIGNED',
                "New Work Order {$code}",
                "{$task->title} — {$task->customer_name} ({$task->resolved_location})",
                ['task_id' => (string) $task->id, 'event' => 'TASK_ASSIGNED']
            );
        }

        // 2. Customer WhatsApp / Email notification with live tracking link
        if ($task->customer_phone) {
            $trackUrl = $task->tracking_url ?: url('/track/' . $task->ensureTrackingToken());
            $techName = $tech ? \Illuminate\Support\Str::before(trim($tech->name), ' ') : 'Field Engineer';
            $msg = "FiberOps Update: Ticket {$code} has been assigned to technician {$techName}. Track live arrival here: {$trackUrl}";
            $this->enqueueWhatsApp($task->id, $task->customer_phone, 'TASK_ASSIGNED', $msg);
        }

        if ($task->customer_email) {
            $trackUrl = $task->tracking_url ?: url('/track/' . $task->ensureTrackingToken());
            $this->enqueueEmail(
                $task->id,
                $task->customer_email,
                'TASK_ASSIGNED',
                "Technician Assigned — {$code}",
                "Hello {$task->customer_name},\n\nYour service ticket {$code} ({$task->title}) has been dispatched to our field engineer.\nLive Tracking Radar: {$trackUrl}\n\nThank you."
            );
        }
    }

    public function notifyClientRequestReceived(Task $task): void
    {
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $trackUrl = $task->tracking_url ?: url('/track/' . $task->ensureTrackingToken());

        if ($task->customer_phone) {
            $msg = "FiberOps NOC: Your request {$code} ({$task->title}) is in our live dispatch queue. Track live status: {$trackUrl}";
            $this->enqueueWhatsApp($task->id, $task->customer_phone, 'CLIENT_REQUEST_RECEIVED', $msg);
        }

        if ($task->customer_email) {
            $this->enqueueEmail(
                $task->id,
                $task->customer_email,
                'CLIENT_REQUEST_RECEIVED',
                "Service Request Confirmed — {$code}",
                "Hello {$task->customer_name},\n\nWe received your service request {$code}: {$task->title}.\nTrack live dispatch status here: {$trackUrl}\n\nNOC Support Team"
            );
        }
    }

    public function notifyTaskCompleted(Task $task): void
    {
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $trackUrl = $task->tracking_url ?: url('/track/' . $task->ensureTrackingToken());
        $bastUrl = route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->ensureTrackingToken()]);

        if ($task->customer_phone) {
            $msg = "FiberOps Complete: Ticket {$code} is finished! Download your official BAST PDF ({$bastUrl}) & rate our technician: {$trackUrl}";
            $this->enqueueWhatsApp($task->id, $task->customer_phone, 'TASK_COMPLETED', $msg);
        }

        if ($task->customer_email) {
            $this->enqueueEmail(
                $task->id,
                $task->customer_email,
                'TASK_COMPLETED',
                "Work Order Completed & BAST Ready — {$code}",
                "Hello {$task->customer_name},\n\nYour work order {$code} has been completed and cryptographically sealed.\nOfficial BAST PDF: {$bastUrl}\nRate our service (1–5 stars): {$trackUrl}\n"
            );
        }
    }

    public function notifyPaymentLinkReady(Task $task, \App\Models\PaymentTransaction $tx): void
    {
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $currency = strtoupper((string) ($tx->metadata['currency'] ?? 'USD'));
        $formattedAmount = $currency === 'IDR'
            ? 'IDR ' . number_format((float) $tx->amount, 0)
            : $currency . ' ' . number_format((float) $tx->amount, 2);
        $payLink = str_starts_with((string) $tx->qr_payload, 'http')
            ? $tx->qr_payload
            : ($task->tracking_url ?: url('/track/' . $task->ensureTrackingToken()));

        if ($task->customer_phone) {
            $msg = "FiberOps Billing: Your service invoice {$code} ({$formattedAmount}) is ready. Pay securely online to activate/restore internet automatically: {$payLink}";
            $this->enqueueWhatsApp($task->id, $task->customer_phone, 'PAYMENT_LINK_READY', $msg);
        }

        if ($task->customer_email) {
            $this->enqueueEmail(
                $task->id,
                $task->customer_email,
                'PAYMENT_LINK_READY',
                "Service Invoice Ready — {$code} ({$formattedAmount})",
                "Hello {$task->customer_name},\n\nYour invoice {$code} for {$formattedAmount} is ready.\nSecure Payment Link: {$payLink}\n\nYour fiber internet restores automatically within seconds after payment."
            );
        }
    }

    public function notifyPaymentSettledAndUnblocked(Task $task, \App\Models\PaymentTransaction $tx): void
    {
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $currency = strtoupper((string) ($tx->metadata['currency'] ?? 'USD'));
        $formattedAmount = $currency === 'IDR'
            ? 'IDR ' . number_format((float) $tx->amount, 0)
            : $currency . ' ' . number_format((float) $tx->amount, 2);
        $verifyUrl = !empty($task->bast_hash)
            ? url('/bast/verify/' . $task->bast_hash)
            : ($task->tracking_url ?: url('/track/' . $task->ensureTrackingToken()));

        if ($task->customer_phone) {
            $msg = "FiberOps Payment Confirmed ({$formattedAmount}, Ref: {$tx->reference_no}): Thank you! Your MikroTik internet access is being restored automatically. Receipt/Status: {$verifyUrl}";
            $this->enqueueWhatsApp($task->id, $task->customer_phone, 'PAYMENT_SETTLED_UNBLOCKED', $msg);
        }

        if ($task->customer_email) {
            $this->enqueueEmail(
                $task->id,
                $task->customer_email,
                'PAYMENT_SETTLED_UNBLOCKED',
                "Payment Confirmed & Service Restored — {$code}",
                "Hello {$task->customer_name},\n\nWe received your payment of {$formattedAmount} (Ref: {$tx->reference_no}).\nYour fiber connection is restoring automatically on your ISP router.\nStatus & Certificate: {$verifyUrl}\n"
            );
        }
    }

    public function dispatchWebhookAsync(string $event, array $payload, ?int $taskId = null): void
    {
        $log = $this->recordLog($taskId, 'webhook', SettingService::get('webhook_url', ''), $event, $payload);
        if (!$log) {
            return;
        }

        $this->deferDelivery($log->id);
    }

    public function enqueuePushToUser(int $userId, ?int $taskId, string $event, string $title, string $body, array $data = []): void
    {
        if (!Schema::hasTable('device_tokens')) {
            return;
        }

        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            $log = $this->recordLog($taskId, 'fcm', $token, $event, [
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ]);
            if ($log) {
                $this->deferDelivery($log->id);
            }
        }
    }

    public function enqueueWhatsApp(?int $taskId, string $phone, string $event, string $message): void
    {
        if (SettingService::get('notify_customer_whatsapp', '0') !== '1') {
            return;
        }

        $log = $this->recordLog($taskId, 'whatsapp', $phone, $event, ['message' => $message]);
        if ($log) {
            $this->deferDelivery($log->id);
        }
    }

    public function enqueueEmail(?int $taskId, string $email, string $event, string $subject, string $body): void
    {
        if (SettingService::get('notify_customer_email', '0') !== '1') {
            return;
        }

        $log = $this->recordLog($taskId, 'email', $email, $event, [
            'subject' => $subject,
            'body'    => $body,
        ]);
        if ($log) {
            $this->deferDelivery($log->id);
        }
    }

    /**
     * Deliver a single logged notification synchronously (called inside afterResponse or retry cron).
     */
    public function deliverLog(NotificationLog $log): bool
    {
        $settings = SettingService::all();
        $log->increment('attempts');

        try {
            if ($log->channel === 'webhook') {
                $url = trim((string) ($settings['webhook_url'] ?? ''));
                if ($url === '' || str_contains($url, 'bot-sample-token') || !SettingService::isSafeExternalUrl($url)) {
                    $log->update(['status' => 'skipped', 'last_error' => 'Webhook URL not configured or blocked by SSRF guard.']);
                    return false;
                }
                Http::timeout(2.5)->withHeaders(['Content-Type' => 'application/json'])->post($url, [
                    'event'       => $log->event,
                    'source'      => 'FiberOps FSM Dispatcher',
                    'timestamp'   => now()->toIso8601String(),
                    'data'        => $log->payload ?? [],
                ])->throw();
            } elseif ($log->channel === 'whatsapp') {
                $gwUrl = trim((string) ($settings['whatsapp_gateway_url'] ?? ''));
                $gwToken = trim((string) ($settings['whatsapp_gateway_token'] ?? ''));
                if ($gwUrl === '' || !SettingService::isSafeExternalUrl($gwUrl)) {
                    $log->update(['status' => 'skipped', 'last_error' => 'WhatsApp Gateway URL not configured or blocked.']);
                    return false;
                }
                $req = Http::timeout(4.0);
                if ($gwToken !== '') {
                    $req = $req->withHeaders(['Authorization' => $gwToken]);
                }
                $req->post($gwUrl, [
                    'target'  => $log->recipient,
                    'phone'   => $log->recipient,
                    'message' => (string) ($log->payload['message'] ?? ''),
                ])->throw();
            } elseif ($log->channel === 'fcm') {
                $fcmKey = trim((string) ($settings['fcm_server_key'] ?? env('FCM_SERVER_KEY', '')));
                $fcmUrl = trim((string) ($settings['fcm_endpoint'] ?? env('FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send')));
                if ($fcmKey === '' || !SettingService::isSafeExternalUrl($fcmUrl)) {
                    $log->update(['status' => 'skipped', 'last_error' => 'FCM credentials not configured yet.']);
                    return false;
                }
                Http::timeout(4.0)->withHeaders([
                    'Authorization' => 'key=' . $fcmKey,
                    'Content-Type'  => 'application/json',
                ])->post($fcmUrl, [
                    'to'           => $log->recipient,
                    'notification' => [
                        'title' => (string) ($log->payload['title'] ?? 'FiberOps Alert'),
                        'body'  => (string) ($log->payload['body'] ?? ''),
                        'sound' => 'default',
                    ],
                    'data'         => $log->payload['data'] ?? [],
                ])->throw();
            } elseif ($log->channel === 'email') {
                $subject = (string) ($log->payload['subject'] ?? 'FiberOps Notification');
                $body = (string) ($log->payload['body'] ?? '');
                Mail::raw($body, function ($m) use ($log, $subject) {
                    $m->to((string) $log->recipient)->subject($subject);
                });
            }

            $log->update([
                'status'     => 'sent',
                'last_error' => null,
                'sent_at'    => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning("[NotificationService] {$log->channel} failed for log #{$log->id}: " . $e->getMessage());
            $log->update([
                'status'     => 'failed',
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ]);
            return false;
        }
    }

    /**
     * Retry up to 25 failed/queued notifications (for cPanel cron).
     */
    public function retryPending(int $limit = 25): int
    {
        if (!Schema::hasTable('notification_logs')) {
            return 0;
        }

        $logs = NotificationLog::whereIn('status', ['queued', 'failed'])
            ->where('attempts', '<', 3)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $delivered = 0;
        foreach ($logs as $log) {
            if ($this->deliverLog($log)) {
                $delivered++;
            }
        }
        return $delivered;
    }

    private function recordLog(?int $taskId, string $channel, ?string $recipient, string $event, array $payload): ?NotificationLog
    {
        if (!Schema::hasTable('notification_logs')) {
            return null;
        }

        return NotificationLog::create([
            'task_id'   => $taskId,
            'channel'   => $channel,
            'recipient' => $recipient ? mb_substr($recipient, 0, 255) : null,
            'event'     => $event,
            'payload'   => $payload,
            'status'    => 'queued',
            'attempts'  => 0,
        ]);
    }

    private function deferDelivery(int $logId): void
    {
        // Never block HTTP response; execute cleanly after response flush (or skip external HTTP during unit tests unless explicitly invoked)
        if (app()->runningUnitTests()) {
            return;
        }

        dispatch(function () use ($logId) {
            $log = NotificationLog::find($logId);
            if ($log && $log->status === 'queued') {
                app(self::class)->deliverLog($log);
            }
        })->afterResponse();
    }
}
