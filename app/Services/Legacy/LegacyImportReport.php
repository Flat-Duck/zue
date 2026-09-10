<?php

namespace App\Services\Legacy;

/**
 * The outcome of a legacy import run: what landed, what was deliberately left
 * behind, and every identity decision that had to be inferred rather than read.
 */
class LegacyImportReport
{
    /**
     * @param  array<string, int>  $rowCounts
     * @param  array<string, string>  $skipped  table => reason
     * @param  list<array{user_id: int, email: ?string, name: ?string, number: ?int}>  $unresolvedUsers
     * @param  list<array{employee_id: int, kept_user_id: int, dropped_user_id: int, dropped_email: ?string, dropped_name: ?string}>  $identityConflicts
     * @param  list<array{user_id: int, email: string}>  $emailConflicts
     * @param  array<int, int>  $matchRankCounts
     * @param  list<int>  $unresolvedReviserNumbers
     * @param  array<string, array<int, int>>  $orphanedReferences  "table.column" => [employee id => rows]
     */
    public function __construct(
        public readonly bool $dryRun,
        public readonly array $rowCounts,
        public readonly array $skipped,
        public readonly array $unresolvedUsers,
        public readonly array $identityConflicts,
        public readonly array $emailConflicts,
        public readonly array $matchRankCounts,
        public readonly array $unresolvedReviserNumbers,
        public readonly int $timeSheetsWithReviser,
        public readonly array $orphanedReferences = [],
    ) {}

    public function hasIdentityProblems(): bool
    {
        return $this->unresolvedUsers !== []
            || $this->identityConflicts !== []
            || $this->emailConflicts !== []
            || $this->unresolvedReviserNumbers !== []
            || $this->orphanedReferences !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dry_run' => $this->dryRun,
            'generated_at' => now()->toIso8601String(),
            'row_counts' => $this->rowCounts,
            'skipped_tables' => $this->skipped,
            'identity' => [
                'match_ranks' => collect($this->matchRankCounts)
                    ->mapWithKeys(fn (int $count, int $rank): array => [LegacyIdentityMap::describeRank($rank) => $count])
                    ->all(),
                'unresolved_users' => $this->unresolvedUsers,
                'conflicts' => $this->identityConflicts,
                'email_conflicts' => $this->emailConflicts,
            ],
            'orphaned_references' => $this->orphanedReferences,
            'time_sheets' => [
                'rows_with_reviser' => $this->timeSheetsWithReviser,
                'unresolved_reviser_numbers' => $this->unresolvedReviserNumbers,
            ],
        ];
    }
}
