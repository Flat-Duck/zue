<?php

namespace App\Imports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ArchivedEmployeesImport implements ToCollection, WithChunkReading
{
    public function collection(Collection $rows): void
    {
        $employeeIds = $rows
            ->pluck(0)
            ->filter()
            ->map(fn ($employeeId): int => (int) $employeeId)
            ->unique()
            ->values();

        Employee::query()
            ->whereIn('id', $employeeIds)
            ->update(['archived_at' => now()]);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
