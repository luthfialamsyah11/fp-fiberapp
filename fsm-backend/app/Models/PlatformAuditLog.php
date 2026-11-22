<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Strictly immutable, SHA-256 hash-chained governance audit ledger
 * for Platform Master Console and ISP onboarding lifecycle events.
 */
class PlatformAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'platform_audit_logs';

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'target_tenant_id',
        'target_tenant_name',
        'action',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
        'prev_hash',
        'entry_hash',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
        'created_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Platform audit logs are strictly immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Platform audit logs are strictly immutable and cannot be deleted.');
        });
    }

    /**
     * Append a new cryptographically chained entry to the platform audit ledger.
     */
    public static function record(
        string $action,
        ?Tenant $targetTenant = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?User $actor = null,
        ?string $actorNameOverride = null,
        ?string $actorEmailOverride = null
    ): self {
        return DB::transaction(function () use (
            $action,
            $targetTenant,
            $beforeState,
            $afterState,
            $actor,
            $actorNameOverride,
            $actorEmailOverride
        ) {
            $resolvedActor = $actor ?? auth()->user();
            $actorId = $resolvedActor?->id;
            $actorName = $actorNameOverride ?? ($resolvedActor?->name ?? 'Public / System');
            $actorEmail = $actorEmailOverride ?? ($resolvedActor?->email ?? 'system@bentengsiber.com');

            $ip = request()?->ip();
            $ua = request()?->userAgent();
            $createdAt = now()->startOfSecond();

            $prevHash = static::orderByDesc('id')->lockForUpdate()->value('entry_hash')
                ?? str_repeat('0', 64);

            $entryHash = static::computeEntryHash(
                $prevHash,
                $actorId,
                $actorEmail,
                $targetTenant?->id,
                $action,
                $beforeState,
                $afterState,
                $ip,
                $createdAt
            );

            return static::create([
                'actor_id'           => $actorId,
                'actor_name'         => substr($actorName, 0, 160),
                'actor_email'        => substr($actorEmail, 0, 190),
                'target_tenant_id'   => $targetTenant?->id,
                'target_tenant_name' => $targetTenant ? substr($targetTenant->name, 0, 190) : null,
                'action'             => $action,
                'before_state'       => $beforeState,
                'after_state'        => $afterState,
                'ip_address'         => $ip ? substr($ip, 0, 45) : null,
                'user_agent'         => $ua ? substr($ua, 0, 500) : null,
                'prev_hash'          => $prevHash,
                'entry_hash'         => $entryHash,
                'created_at'         => $createdAt,
            ]);
        });
    }

    public static function computeEntryHash(
        string $prevHash,
        ?int $actorId,
        string $actorEmail,
        ?int $targetTenantId,
        string $action,
        ?array $beforeState,
        ?array $afterState,
        ?string $ipAddress,
        Carbon $createdAt
    ): string {
        $canonical = [
            'prev_hash'        => $prevHash,
            'actor_id'         => $actorId,
            'actor_email'      => $actorEmail,
            'target_tenant_id' => $targetTenantId,
            'action'           => $action,
            'before_state'     => $beforeState,
            'after_state'      => $afterState,
            'ip_address'       => $ipAddress,
            'created_at'       => $createdAt->utc()->toIso8601String(),
        ];

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify entire SHA-256 hash chain from genesis to head.
     *
     * @return array{valid: bool, checked: int, broken_id: ?int}
     */
    public static function verifyChainIntegrity(): array
    {
        $entries = static::orderBy('id', 'asc')->get();
        $expectedPrev = str_repeat('0', 64);
        $checked = 0;

        foreach ($entries as $entry) {
            $checked++;
            if (!hash_equals($expectedPrev, (string) $entry->prev_hash)) {
                return ['valid' => false, 'checked' => $checked, 'broken_id' => $entry->id];
            }

            $recomputed = static::computeEntryHash(
                (string) $entry->prev_hash,
                $entry->actor_id !== null ? (int) $entry->actor_id : null,
                (string) $entry->actor_email,
                $entry->target_tenant_id !== null ? (int) $entry->target_tenant_id : null,
                (string) $entry->action,
                $entry->before_state,
                $entry->after_state,
                $entry->ip_address,
                $entry->created_at instanceof Carbon ? $entry->created_at : Carbon::parse($entry->created_at)
            );

            if (!hash_equals($recomputed, (string) $entry->entry_hash)) {
                return ['valid' => false, 'checked' => $checked, 'broken_id' => $entry->id];
            }

            $expectedPrev = (string) $entry->entry_hash;
        }

        return ['valid' => true, 'checked' => $checked, 'broken_id' => null];
    }
}
