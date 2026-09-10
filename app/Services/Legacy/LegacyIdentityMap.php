<?php

namespace App\Services\Legacy;

/**
 * Rebuilds the actor identity the legacy schema never stored.
 *
 * The old system gave a user the employee's number as its primary key, then later
 * grew a separate `number` column, and `employees.user_id` was left NULL on every
 * row. Nothing in the dump states which employee a user is, so the link is derived
 * here — by employee number first, and only then by the old id convention — and
 * every derivation records how confident it is so the result can be audited.
 *
 * Employee ids are carried over from the dump unchanged. They are the anchor the
 * whole import hangs on: time sheets, management scopes and approval steps all
 * reference them, so translating them would rewrite 1.2M rows for no gain.
 */
class LegacyIdentityMap
{
    public const MATCH_USER_NUMBER = 1;

    public const MATCH_USER_ID_AS_NUMBER = 2;

    public const MATCH_USER_ID_AS_EMPLOYEE_ID = 3;

    /** @var array<int, int> employee number => employee id */
    private array $employeeIdByNumber = [];

    /** @var array<int, true> employee id */
    private array $knownEmployeeIds = [];

    /** @var array<int, array{employee_id: int, rank: int}> legacy user id => resolution */
    private array $resolvedUsers = [];

    /** @var list<array{user_id: int, email: ?string, name: ?string, number: ?int}> */
    private array $unresolvedUsers = [];

    /** @var list<array{employee_id: int, kept_user_id: int, dropped_user_id: int, dropped_email: ?string, dropped_name: ?string}> */
    private array $conflicts = [];

    /** @var array<int, int> legacy user id => new user id */
    private array $newUserIds = [];

    /** @var array<int, array{name: ?string, email: ?string, number: ?int}> legacy user id => details, kept so a displaced user can still be named in the report */
    private array $userDetails = [];

    public function registerEmployee(int $id, ?int $number): void
    {
        $this->knownEmployeeIds[$id] = true;

        if ($number !== null && ! isset($this->employeeIdByNumber[$number])) {
            $this->employeeIdByNumber[$number] = $id;
        }
    }

    /**
     * Records a legacy user and works out which employee it belongs to.
     *
     * Returns false when no employee can be found, in which case the user cannot be
     * imported at all: `users.employee_id` is NOT NULL by design.
     */
    public function registerUser(int $legacyUserId, ?int $legacyNumber, ?string $name, ?string $email): bool
    {
        $this->userDetails[$legacyUserId] = ['name' => $name, 'email' => $email, 'number' => $legacyNumber];

        $resolution = $this->resolve($legacyUserId, $legacyNumber);

        if ($resolution === null) {
            $this->unresolvedUsers[] = [
                'user_id' => $legacyUserId,
                'email' => $email,
                'name' => $name,
                'number' => $legacyNumber,
            ];

            return false;
        }

        [$employeeId, $rank] = $resolution;

        $incumbent = $this->userHoldingEmployee($employeeId);

        if ($incumbent === null) {
            $this->resolvedUsers[$legacyUserId] = ['employee_id' => $employeeId, 'rank' => $rank];

            return true;
        }

        if ($this->beats($rank, $legacyUserId, $this->resolvedUsers[$incumbent]['rank'], $incumbent)) {
            unset($this->resolvedUsers[$incumbent]);
            $this->resolvedUsers[$legacyUserId] = ['employee_id' => $employeeId, 'rank' => $rank];
            $this->conflicts[] = [
                'employee_id' => $employeeId,
                'kept_user_id' => $legacyUserId,
                'dropped_user_id' => $incumbent,
                'dropped_email' => $this->userDetails[$incumbent]['email'] ?? null,
                'dropped_name' => $this->userDetails[$incumbent]['name'] ?? null,
            ];

            return true;
        }

        $this->conflicts[] = [
            'employee_id' => $employeeId,
            'kept_user_id' => $incumbent,
            'dropped_user_id' => $legacyUserId,
            'dropped_email' => $email,
            'dropped_name' => $name,
        ];

        return false;
    }

    public function employeeIdForUser(int $legacyUserId): ?int
    {
        return $this->resolvedUsers[$legacyUserId]['employee_id'] ?? null;
    }

    public function matchRankForUser(int $legacyUserId): ?int
    {
        return $this->resolvedUsers[$legacyUserId]['rank'] ?? null;
    }

    /**
     * Translates a legacy `admin_id` — an employee *number*, not an id — into the
     * employee id the new schema's foreign key expects.
     */
    public function employeeIdForNumber(?int $number): ?int
    {
        if ($number === null) {
            return null;
        }

        return $this->employeeIdByNumber[$number]
            ?? (isset($this->knownEmployeeIds[$number]) ? $number : null);
    }

    public function recordNewUserId(int $legacyUserId, int $newUserId): void
    {
        $this->newUserIds[$legacyUserId] = $newUserId;
    }

    public function newUserId(int $legacyUserId): ?int
    {
        return $this->newUserIds[$legacyUserId] ?? null;
    }

    /**
     * @return array<int, array{employee_id: int, rank: int}>
     */
    public function resolvedUsers(): array
    {
        return $this->resolvedUsers;
    }

    /**
     * @return list<array{user_id: int, email: ?string, name: ?string, number: ?int}>
     */
    public function unresolvedUsers(): array
    {
        return $this->unresolvedUsers;
    }

    /**
     * @return list<array{employee_id: int, kept_user_id: int, dropped_user_id: int, dropped_email: ?string, dropped_name: ?string}>
     */
    public function conflicts(): array
    {
        return $this->conflicts;
    }

    public function hasEmployee(int $employeeId): bool
    {
        return isset($this->knownEmployeeIds[$employeeId]);
    }

    public function employeeCount(): int
    {
        return count($this->knownEmployeeIds);
    }

    public static function describeRank(int $rank): string
    {
        return match ($rank) {
            self::MATCH_USER_NUMBER => 'users.number matched employees.number',
            self::MATCH_USER_ID_AS_NUMBER => 'users.id matched employees.number',
            self::MATCH_USER_ID_AS_EMPLOYEE_ID => 'users.id matched employees.id',
            default => 'unknown',
        };
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function resolve(int $legacyUserId, ?int $legacyNumber): ?array
    {
        if ($legacyNumber !== null && isset($this->employeeIdByNumber[$legacyNumber])) {
            return [$this->employeeIdByNumber[$legacyNumber], self::MATCH_USER_NUMBER];
        }

        if (isset($this->employeeIdByNumber[$legacyUserId])) {
            return [$this->employeeIdByNumber[$legacyUserId], self::MATCH_USER_ID_AS_NUMBER];
        }

        if (isset($this->knownEmployeeIds[$legacyUserId])) {
            return [$legacyUserId, self::MATCH_USER_ID_AS_EMPLOYEE_ID];
        }

        return null;
    }

    private function userHoldingEmployee(int $employeeId): ?int
    {
        foreach ($this->resolvedUsers as $userId => $resolution) {
            if ($resolution['employee_id'] === $employeeId) {
                return $userId;
            }
        }

        return null;
    }

    /**
     * A stronger match always wins; between equal matches the later row wins, since
     * that is the one the HR office most recently maintained.
     */
    private function beats(int $rank, int $userId, int $incumbentRank, int $incumbentUserId): bool
    {
        if ($rank !== $incumbentRank) {
            return $rank < $incumbentRank;
        }

        return $userId > $incumbentUserId;
    }
}
