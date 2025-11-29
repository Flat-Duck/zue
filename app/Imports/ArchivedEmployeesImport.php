<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ArchivedEmployeesImport implements ToCollection
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            $employee = Employee::find( $row[0]);

            if ($employee) {
                $employee->archived_at = now();
                $employee->save();
            }
        }
    }
}
