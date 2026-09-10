<?php

// app/Exports/EmployeeBalanceExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeBalanceExport implements WithMultipleSheets
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function sheets(): array
    {
        $sheets = [];

        $groupedEmployees = $this->employees->groupBy(function ($employee) {
            return $employee->location->name.'-'.$employee->department->name;
        });

        foreach ($groupedEmployees as $sheetName => $employees) {
            $sheets[] = new EmployeeSheetExport($sheetName, $employees);
        }

        return $sheets;
    }
}

// namespace App\Exports;

// use App\Models\Employee;
// use Maatwebsite\Excel\Concerns\Exportable;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithMapping;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// class EmployeeBalanceExport implements FromCollection, WithMapping,WithHeadings //, WithMultipleSheets
// {
//     use Exportable;

//     protected $centers;
//     protected $employees;

//     public function __construct($employees)
//     {
//         $this->employees = $employees;
//     }
//     /**
//     * @return \Illuminate\Support\Collection
//     */
//     public function collection()
//     {
//         return $this->employees->first();
//     }

//     public function map($employee): array
//     {
//         return [
//             // here should be counter for each row
//             $employee->number,
//             $employee->english_name,
//             $employee->start_date,
//             $employee->department->name,
//             $employee->center->name,
//             $employee->location->name,
//             $employee->schedule,
//             $employee->last_date,
//             $employee->total_balance,

//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             '#',
//             'No',
//             'Name',
//             'Start Date',
//             'Section',
//             'CC',
//             'LOC',
//             'SCH',
//             'ToDate',
//             'Balance',
//         ];
//     }

//     // /**
//     //  * @return array
//     //  */
//     // public function sheets(): array
//     // {
//     //     $sheets = [];

//     //     for ($month = 1; $month <= 12; $month++) {
//     //         $sheets[] = new InvoicesPerMonthSheet($this->year, $month);
//     //     }

//     //     return $sheets;
//     // }
// }
