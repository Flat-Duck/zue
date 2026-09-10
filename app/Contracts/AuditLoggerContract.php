<?php

namespace App\Contracts;

/**
 * Records security-relevant events.
 *
 * An interface because auditing is a cross-cutting concern reached from
 * controllers, Livewire components and services alike, and because a test that
 * asserts "this action was audited" should be able to substitute a recorder
 * rather than reach into the logging channel.
 */
interface AuditLoggerContract
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $action, array $context = []): void;

    /**
     * A refused or failed attempt, which is at least as interesting as a
     * successful one.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordFailure(string $action, string $reason, array $context = []): void;
}
