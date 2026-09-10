<?php

namespace App\Services;

use App\Contracts\AuditLoggerContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

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
class AuditLogger implements AuditLoggerContract
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
     * Session keys written when a super-admin is impersonating someone.
     */
    public const IMPERSONATOR_ID_SESSION_KEY = 'impersonator_id';

    public const IMPERSONATOR_NAME_SESSION_KEY = 'impersonator_name';

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

        $context = [
            'actor_id' => $user?->getAuthIdentifier(),
            'actor_name' => $user?->name,
            'ip' => Request::ip(),
            'at' => now()->toIso8601String(),
        ];

        /*
         * While a super-admin is impersonating, Auth::user() is the person being
         * impersonated. Recording only that would attribute their actions to the
         * wrong person, which defeats the purpose of an audit trail, so the real
         * operator is named alongside them.
         */
        $impersonatorId = Session::get(self::IMPERSONATOR_ID_SESSION_KEY);

        if ($impersonatorId !== null) {
            $context['impersonated'] = true;
            $context['impersonator_id'] = $impersonatorId;
            $context['impersonator_name'] = Session::get(self::IMPERSONATOR_NAME_SESSION_KEY);
        }

        return $context;
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
