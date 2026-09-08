<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Residence;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RoomsImport implements ToCollection, WithChunkReading
{
    public function collection(Collection $rows): void
    {
        $employeeIds = $rows
            ->pluck(0)
            ->filter(fn ($employeeId): bool => filled($employeeId) && ! Str::contains((string) $employeeId, '+'))
            ->map(fn ($employeeId): int => (int) $employeeId)
            ->unique()
            ->values();

        $existingEmployeeIds = Employee::query()
            ->whereIn('id', $employeeIds)
            ->pluck('id')
            ->map(fn ($employeeId): int => (int) $employeeId)
            ->flip();

        foreach ($rows as $row) {
            if (! isset($row[0], $row[1], $row[2], $row[3])) {
                continue;
            }

            $employeeId = (int) $row[0];
            if (! $existingEmployeeIds->has($employeeId)) {
                continue;
            }

            $residenceId = $this->getResident((string) $row[2], (string) $row[1]);
            $room = Room::firstOrCreate(
                [
                    'number' => (string) $row[3],
                    'residence_id' => $residenceId,
                ],
                [
                    'beds' => 1,
                ]
            );

            $room->employees()->syncWithoutDetaching([
                $employeeId => ['is_owner' => true, 'is_here' => true],
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function getResident(string $name, string $type): int
    {
        $residence = Residence::firstOrCreate(
            [
                'name' => $name,
                'type' => $type,
            ],
            [
                'name' => $name,
                'type' => $type,
            ]
        );

        return (int) $residence->id;
    }
}
