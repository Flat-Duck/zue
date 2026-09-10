<?php

namespace App\Imports;

use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Throwable;

/**
 * Imports the personnel export into employee records.
 *
 * Columns are read by position rather than by heading: the export repeats the
 * headings "الوظيفة" and "ملاحظة", so keying on names would silently discard
 * one of each pair.
 *
 * Employee number is the key. An existing employee is updated and a new one is
 * created, so the same file can be re-imported as the source data changes.
 */
class EmployeeProfilesImport extends StringValueBinder implements ToCollection, WithChunkReading, WithCustomValueBinder
{
    /**
     * Values the export uses to mean "nothing recorded".
     */
    private const BLANK_MARKERS = ['', '/', '\\', '-', '0'];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var array<string, int> */
    private array $referenceCache = [];

    /**
     * Column positions in the export, zero based.
     */
    private const COLUMNS = [
        'number' => 0,
        'arabic_name' => 1,
        'arabic_full_name' => 2,
        'nationality' => 9,
        'salary_type' => 10,
        'basic_salary' => 11,
        'total_salary' => 12,
        'performance_incentive' => 13,
        'secondment_allowance' => 14,
        'living_allowance' => 15,
        'expatriation_allowance' => 16,
        'desert_allowance' => 17,
        'job_code' => 18,
        'job' => 19,
        'job_title_en' => 20,
        'job_start_date' => 21,
        'job_note' => 22,
        'job_level' => 23,
        'old_grade' => 24,
        'grade' => 25,
        'job_number' => 26,
        'employment_type' => 27,
        'assignment_authority' => 28,
        'appointment_date' => 29,
        'appointment_job' => 30,
        'appointment_decision_number' => 31,
        'birth_date' => 32,
        'id_card' => 33,
        'passport' => 34,
        'insurance_id' => 35,
        'marital_status' => 36,
        'gender' => 37,
        'file_number' => 38,
        'family_paper_number' => 39,
        'family_record_number' => 40,
        'full_record_number' => 41,
        'address' => 42,
        'city' => 43,
        'email' => 44,
        'phone' => 45,
        'education_level' => 46,
        'specialization' => 47,
        'education_year' => 48,
        'study_country' => 49,
        'study_institution' => 50,
        'education_level_code' => 51,
        'clinic_name' => 52,
        'children_count' => 53,
        'family_members_count' => 54,
        'previous_employer' => 55,
        'previous_job' => 56,
        'previous_job_start' => 57,
        'previous_job_end' => 58,
        'years_of_experience' => 59,
        'mother_name' => 60,
        'mother_name_insurance' => 61,
        'birth_place' => 62,
        'action_code' => 63,
        'action_date' => 64,
        'military_status' => 65,
        'marriage_grant_date' => 66,
        'unpaid_leave_start' => 67,
        'unpaid_leave_end' => 68,
        'social_security_number' => 69,
        'bank_name' => 70,
        'bank_account_number' => 71,
        'hr_notes' => 72,
    ];

    /**
     * Organisational columns. These are not stored on the employee: they
     * identify the centre, administration, department and location the
     * employee belongs to, and the employee keeps only the foreign keys.
     */
    private const ORGANISATION_COLUMNS = [
        'cost_center' => 3,
        'administration_name' => 4,
        'department_name' => 5,
        'department_code' => 6,
        'location_code' => 7,
        'location_name' => 8,
    ];

    private const DATE_FIELDS = [
        'birth_date', 'appointment_date', 'job_start_date',
        'unpaid_leave_start', 'unpaid_leave_end',
    ];

    private const DECIMAL_FIELDS = [
        'basic_salary', 'total_salary', 'performance_incentive', 'secondment_allowance',
        'living_allowance', 'expatriation_allowance', 'desert_allowance',
    ];

    private const INTEGER_FIELDS = ['children_count', 'family_members_count'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $values = $row instanceof Collection ? $row->all() : (array) $row;

            // The export repeats its heading row; skip it wherever it appears.
            $first = $this->raw($values, 0);

            if ($first === null || ! is_numeric($first)) {
                continue;
            }

            try {
                $this->importRow($values);
            } catch (Throwable $exception) {
                $this->skipped++;
                $this->errors[] = "Row {$index}: ".$exception->getMessage();
            }
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function importRow(array $values): void
    {
        $number = (int) $this->raw($values, self::COLUMNS['number']);

        if ($number < 1) {
            $this->skipped++;

            return;
        }

        $attributes = [];

        foreach (self::COLUMNS as $field => $position) {
            if ($field === 'number') {
                continue;
            }

            $value = $this->clean($this->raw($values, $position));

            if ($value === null) {
                continue;
            }

            $attributes[$field] = match (true) {
                in_array($field, self::DATE_FIELDS, true) => $this->toDate($value),
                in_array($field, self::DECIMAL_FIELDS, true) => $this->toDecimal($value),
                in_array($field, self::INTEGER_FIELDS, true) => $this->toInteger($value),
                $field === 'bank_account_number' => $this->accountNumber($value),
                default => $value,
            };
        }

        // Nulls produced by parsing are dropped so an unreadable value never
        // wipes a good one already on the record.
        $attributes = array_filter($attributes, static fn ($value): bool => $value !== null);

        $attributes += $this->resolveOrganisation($values);

        // Archived employees are hidden by a global scope. Looking through it would
        // make the import create a second row for anyone who has left the company.
        $employee = Employee::query()->withArchived()->where('number', $number)->first();

        if ($employee) {
            $employee->fill($attributes)->save();
            $this->updated++;

            return;
        }

        Employee::query()->create($attributes + ['number' => $number]);
        $this->created++;
    }

    /**
     * Match the employee to its centre, department and location, creating any
     * the business has that the application does not yet know about.
     *
     * The code and Arabic name from the export are written onto those records
     * rather than onto the employee, so one department carries its own details
     * however many people work in it.
     *
     * @param  array<int, mixed>  $values
     * @return array<string, int>
     */
    private function resolveOrganisation(array $values): array
    {
        $source = [];

        foreach (self::ORGANISATION_COLUMNS as $field => $position) {
            $source[$field] = $this->clean($this->raw($values, $position));
        }

        $references = [];

        if ($source['cost_center'] !== null) {
            $references['center_id'] = $this->centre($source['cost_center']);
        }

        $locationKey = $source['location_code'] ?? $source['location_name'];
        if ($locationKey !== null) {
            $references['location_id'] = $this->location($locationKey, $source['location_name']);
        }

        $departmentKey = $source['department_name'] ?? $source['department_code'];
        if ($departmentKey !== null) {
            $references['department_id'] = $this->department(
                $departmentKey,
                $source['department_code'],
                $source['administration_name']
            );
        }

        return $references;
    }

    private function centre(string $code): int
    {
        return $this->remember(Center::class, $code, function () use ($code): Center {
            // Existing rows store the code in `name`, so match on either.
            $centre = Center::query()
                ->where('code', $code)
                ->orWhere('name', $code)
                ->first() ?? new Center(['name' => $code]);

            $centre->code ??= $code;
            $centre->save();

            return $centre;
        });
    }

    private function location(string $key, ?string $arabicName): int
    {
        return $this->remember(Location::class, $key, function () use ($key, $arabicName): Location {
            $location = Location::query()
                ->where('code', $key)
                ->orWhere('name', $key)
                ->first() ?? new Location(['name' => $key]);

            $location->code ??= $key;
            $location->arabic_name ??= $arabicName;
            $location->save();

            return $location;
        });
    }

    private function department(string $key, ?string $code, ?string $administrationName): int
    {
        return $this->remember(Department::class, $key, function () use ($key, $code, $administrationName): Department {
            $department = Department::query()
                ->where('arabic_name', $key)
                ->orWhere('name', $key)
                ->when($code !== null, fn ($query) => $query->orWhere('code', $code))
                ->first() ?? new Department(['name' => $key]);

            $department->code ??= $code;
            $department->arabic_name ??= $key;

            // The export names the administration per employee; it belongs to
            // the department, which is how this application already reads it.
            if ($administrationName !== null && $department->administration_id === null) {
                $department->administration_id = $this->administration($administrationName);
            }

            $department->save();

            return $department;
        });
    }

    private function administration(string $name): int
    {
        return $this->remember(Administration::class, $name, function () use ($name): Administration {
            $administration = Administration::query()
                ->where('arabic_name', $name)
                ->orWhere('name', $name)
                ->first() ?? new Administration(['name' => $name]);

            $administration->arabic_name ??= $name;
            $administration->save();

            return $administration;
        });
    }

    /**
     * @param  class-string  $model
     * @param  \Closure(): Model  $resolver
     */
    private function remember(string $model, string $key, \Closure $resolver): int
    {
        return $this->referenceCache[$model.'|'.$key] ??= (int) $resolver()->getKey();
    }

    private function raw(array $values, int $position): ?string
    {
        $value = $values[$position] ?? null;

        return $value === null ? null : trim((string) $value);
    }

    /**
     * Turn the export's placeholders into nothing at all.
     */
    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return in_array($value, self::BLANK_MARKERS, true) ? null : $value;
    }

    /**
     * The export mixes `1979\03\29` with `10/1/2024`, and contains malformed
     * values such as `26\  \01`. Anything that is not a real date is dropped
     * rather than guessed at.
     */
    private function toDate(string $value): ?string
    {
        $normalised = str_replace(['\\', '.'], '/', $value);

        foreach (['Y/m/d', 'n/j/Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $normalised);

                if ($date && $date->format($format) === $normalised && $date->year > 1900) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // Try the next format.
            }
        }

        return null;
    }

    private function toDecimal(string $value): ?string
    {
        $value = str_replace(',', '', $value);

        return is_numeric($value) ? (string) round((float) $value, 2) : null;
    }

    private function toInteger(string $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Account numbers saved through a spreadsheet are frequently mangled into
     * scientific notation such as `4.10E+13`, which loses the real digits.
     * Storing that would put a plausible-looking but wrong account on a
     * personnel record, so it is refused instead.
     */
    private function accountNumber(string $value): ?string
    {
        if (preg_match('/^\d(?:\.\d+)?[eE][+-]?\d+$/', $value) === 1) {
            $this->errors[] = "Bank account number [{$value}] was corrupted into scientific notation by the spreadsheet and has been skipped.";

            return null;
        }

        return $value;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
