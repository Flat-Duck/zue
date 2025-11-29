<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter;
use Carbon\Carbon;

class EmployeeSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithEvents
{
    protected $sheetName;
    protected $employees;

    public function __construct($sheetName, $employees)
    {
        $this->sheetName = $sheetName;
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees->map(function ($employee, $index) {
            return [
                'index' => $index + 1, // Counter for each row
                'number' => $employee->number,
                'name' => $employee->english_name,
                'start_date' => $employee->start_date,
                'department' => $employee->department->name,
                'center' => $employee->center->name,
                'location' => $employee->location->name,
                'schedule' => $employee->schedule,
                'last_date' => $employee->last_date,
                'total_balance' => $employee->total_balance,
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'No',
            'Name',
            'Start Date',
            'Section',
            'CC',
            'LOC',
            'SCH',
            'ToDate',
            'Balance',
        ];
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            2 => ['font' => ['bold' => true]], // Make the headings row (row 2) bold
            'A2:J' . ($sheet->getHighestRow() + 2) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set page setup for A4 and scale to fit all columns on one page
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0); // Adjust height based on content

                // Add header text (center)
              //  $sheet->getHeaderFooter()->setOddHeader();
                
                // Add the date to the header (right)
                $currentDate = Carbon::now()->format('d/M/Y');
                $sheet->getHeaderFooter()->setOddHeader('&C&BZUEITINA OIL COMPANY \n ACCOUNTING DEPARTMENT 103 \n DETAILED FIELD-BREAK BALANCE &R' . $currentDate);

                // Add logo to the header (left)
                $logoDrawing = new HeaderFooterDrawing();
                $logoDrawing->setName('ZUE Logo');
                $logoDrawing->setPath(public_path('img/zue-logo.png')); // Path to your logo file
                $logoDrawing->setHeight(36); // Adjust as needed
                $sheet->getHeaderFooter()->addImage($logoDrawing, HeaderFooter::IMAGE_HEADER_LEFT);

                // Auto-size columns
                foreach (range('A', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
