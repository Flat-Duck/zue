<?php

namespace App\Services\Health;

/**
 * One thing that can be wrong with the server, and whether it is.
 */
final class HealthCheck
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const FAILING = 'failing';

    public function __construct(
        public readonly string $key,
        public readonly string $status,
        public readonly string $detail,
    ) {}

    public static function ok(string $key, string $detail): self
    {
        return new self($key, self::OK, $detail);
    }

    public static function warning(string $key, string $detail): self
    {
        return new self($key, self::WARNING, $detail);
    }

    public static function failing(string $key, string $detail): self
    {
        return new self($key, self::FAILING, $detail);
    }

    public function isFailing(): bool
    {
        return $this->status === self::FAILING;
    }
}
