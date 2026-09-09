<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Records security-relevant events to a dedicated, long-retention channel.
 *
 * This is an audit trail, not debug logging: it answers "who did what, when"
 * for the actions that matter if something is later disputed or investigated —
 * role changes, medical record edits, approvals, imports, and backup or restore
 * operations.
 *
 * It deliberately records *that* something happened and to which record, never
 * the sensitive content itself. Medical findings and credentials must not be
 * duplicated into a log file that has a longer retention than the data.
 */
class AuditLogger
{
    /**
     * Keys whose values are never written to the audit log, at any depth.
     *
     * @var list<string>
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'api_token',
        'token',
        'secret',
        'authorization',
        'diagnosis',
        'prescription',
        'national_id',
        'passport_number',
    ];

    public const REDACTED = '[redacted]';

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $action, array $context = []): void
    {
        Log::channel('audit')->info($action, $this->baseContext() + [
            'data' => $this->redact($context),
        ]);
    }

    /**
     * A failed or refused attempt is at least as interesting as a successful
     * one, so it gets its own level rather than being buried at info.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordFailure(string $action, string $reason, array $context = []): void
    {
        Log::channel('audit')->warning($action, $this->baseContext() + [
            'reason' => $reason,
            'data' => $this->redact($context),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseContext(): array
    {
        $user = Auth::user();

        return [
            'actor_id' => $user?->getAuthIdentifier(),
            'actor_name' => $user?->name,
            'ip' => Request::ip(),
            'at' => now()->toIso8601String(),
        ];
    }

    /**
     * Replace sensitive values wherever they appear, including nested arrays.
     *
     * Keys are array-key rather than string: nested payloads are frequently
     * lists, and PHP casts numeric string keys to integers.
     *
     * @param  array<array-key, mixed>  $context
     * @return array<array-key, mixed>
     */
    private function redact(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }
}
