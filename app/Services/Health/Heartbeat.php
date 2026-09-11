<?php

namespace App\Services\Health;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Proof that a background process is alive.
 *
 * The scheduler writes one every minute; a job the scheduler queues writes the
 * other when the worker runs it. So a stale scheduler beat means cron has
 * stopped, and a fresh scheduler beat with a stale queue beat means the worker
 * has — which is the failure that quietly stops approvals and notifications
 * while every page still loads.
 */
class Heartbeat
{
    public const SCHEDULER = 'scheduler';

    public const QUEUE = 'queue';

    private const KEY = 'health:heartbeat:';

    public function beat(string $name): void
    {
        Cache::put(self::KEY.$name, now()->toIso8601String(), now()->addDay());
    }

    public function last(string $name): ?CarbonImmutable
    {
        $at = Cache::get(self::KEY.$name);

        return is_string($at) ? CarbonImmutable::parse($at) : null;
    }

    public function ageInMinutes(string $name): ?int
    {
        $last = $this->last($name);

        return $last === null ? null : (int) $last->diffInMinutes(now(), absolute: true);
    }
}
