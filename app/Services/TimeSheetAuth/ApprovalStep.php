<?php

namespace App\Services\TimeSheetAuth;

/**
 * The four stages a monthly time sheet passes through.
 *
 * The vocabulary is shared by the flow definitions, the approval screens and the
 * legacy `time_sheets` columns, and each of those spells it slightly differently.
 * Every translation between them lives here.
 */
enum ApprovalStep: string
{
    case Timekeeper = 'timekeeper';

    case Supervisor = 'supervisor';

    case FieldCoordinator = 'fieldcoordinator';

    case Superintendent = 'superintendent';

    /**
     * Resolves a step by any spelling the application accepts, including the
     * `coordinator` the older screens still send for the field coordinator.
     */
    public static function fromKey(string $key): ?self
    {
        return self::tryFrom(self::normalizeKey($key));
    }

    public static function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));

        return $key === 'coordinator' ? self::FieldCoordinator->value : $key;
    }

    public function label(): string
    {
        return match ($this) {
            self::Timekeeper => 'حافظ الوقت',
            self::Supervisor => 'مشرف القسم',
            self::FieldCoordinator => 'منسق الحقول',
            self::Superintendent => 'مراقب الحقول',
        };
    }

    /**
     * The column on `time_sheets` this stage stamps, kept in step with the approval
     * table so reports written against the old schema keep working. The field
     * coordinator and the superintendent share one column, as they always have.
     */
    public function legacyColumn(): string
    {
        return match ($this) {
            self::Timekeeper => 'timekeeper_id',
            self::Supervisor => 'supervisor_id',
            self::FieldCoordinator, self::Superintendent => 'superintendent_id',
        };
    }
}
