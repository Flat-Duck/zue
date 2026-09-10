<?php

namespace App\Contracts;

/**
 * Produces and verifies database backups.
 *
 * An interface because this is the application's boundary with the filesystem
 * and the database server: it is the piece most worth substituting in a test,
 * and the piece most likely to be replaced with a different strategy.
 */
interface BackupServiceContract
{
    /**
     * @param  list<string>  $selectedTables
     * @return string The stored filename, or the dump itself when not saving.
     */
    public function performBackup(string $type = 'both', array $selectedTables = [], bool $saveToBackups = true, ?int $logId = null): string;

    /**
     * The tables eligible for backup, used as an allow-list.
     *
     * @return list<string>
     */
    public function baseTableNames(): array;
}
