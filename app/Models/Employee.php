<?php

namespace App\Models;

use App\Helpers\TimeSheetBuilder;
use App\Models\Scopes\Searchable;
use App\Models\Scopes\SoftArchives;
use App\Services\Employees\ManagedEmployeeQuery;
use App\Services\Employees\ProfileDefinition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @property-read User|null $user
 * @property-read Signature|null $signature
 */
/**
 * The archive macros come from SoftArchivingScope, which registers them on the query
 * builder rather than declaring them here. Naming them makes them visible to static
 * analysis and to an editor.
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Employee> withArchived(bool $withArchived = true)
 * @method static \Illuminate\Database\Eloquent\Builder<Employee> withoutArchived()
 * @method static \Illuminate\Database\Eloquent\Builder<Employee> onlyArchived()
 */
class Employee extends Model
{
    use HasFactory;
    use Searchable;
    use SoftArchives;
    use SoftDeletes;

    public const SPECIAL_WORK_DAYS_THRESHOLD = 20;

    protected $fillable = [
        'number',
        'job',
        'english_name',
        'location_id',
        'department_id',
        'center_id',
        'transfered_balance',
        'schedule',
        'start_date',
        'last_date',
        'total_balance',
        'archived_at',
        'management_level',
        'employee_level',

        // HR profile fields imported from the personnel export.
        'arabic_name',
        'job_title_en',
    ];

    /**
     * Deliberately empty.
     *
     * Four of the accessors that used to be appended walk a relation, so every
     * serialized employee fetched their own department, administration, location and
     * centre — 44 queries for ten rows. The accessors are all still here: a caller
     * that wants a department name asks for it, and eager-loads when it is a list.
     *
     * @var list<string>
     */
    protected $appends = [];

    protected $searchableFields = ['*'];

    protected $casts = [
        'start_date' => 'date',
        'last_date' => 'date',
        'archived_at' => 'datetime',
    ];

    /**
     * A display name for the employee.
     *
     * Arabic first, because that is what the printed manifests and signature
     * lines show; the English name is the fallback.
     */
    public function getNameAttribute(): ?string
    {
        return $this->arabic_name ?: $this->english_name;
    }

    /**
     * The login account for this employee, if they have one.
     *
     * The link is owned by `users.employee_id`: every user is an employee, but
     * most employees have no login.
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * The signature belongs to the login account, so it is reached through it.
     *
     * @return HasOneThrough<Signature, User, $this>
     */
    public function signature(): HasOneThrough
    {
        return $this->hasOneThrough(
            Signature::class,
            User::class,
            'employee_id',
            'user_id',
            'id',
            'id'
        );
    }

    /**
     * @return HasMany<TimeSheet, $this>
     */
    public function timeSheets(): HasMany
    {
        return $this->hasMany(TimeSheet::class);
    }

    /**
     * @return HasMany<ClinicApointment, $this>
     */
    public function clinicApointments(): HasMany
    {
        return $this->hasMany(ClinicApointment::class);
    }

    /**
     * Clinic appointments grouped by the month they fall in.
     *
     * @return Collection<string, Collection<int, ClinicApointment>>
     */
    public function apointments()
    {
        $appointments = $this->clinicApointments
            ->map(function (ClinicApointment $appointment): ClinicApointment {
                $appointment->year = Carbon::parse($appointment->date)->format('Y');
                $appointment->month = Carbon::parse($appointment->date)->format('F');

                return $appointment;
            });

        return $appointments->groupBy(
            fn (ClinicApointment $appointment): string => $appointment->year.'-'.$appointment->month
        );
    }

    /**
     * The HR profile: identity documents, payroll, education, family, banking.
     *
     * It is a strict 1:1 kept in its own table so that the queries this system runs
     * constantly — listing staff, filling time sheets, printing manifests — do not
     * read a salary or a passport number they will never show.
     *
     * @return HasOne<EmployeeDetail, $this>
     */
    public function details(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class)->withDefault();
    }

    /**
     * What an employee record consists of. The definition itself lives in
     * {@see ProfileDefinition}; these forward to it so callers still ask the model.
     *
     * @return array<string, array{stored_in?: string, arabic: string, fields: array<string, array{label: string, arabic: string, type: string}>}>
     */
    public static function profileSections(): array
    {
        return ProfileDefinition::sections();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function profileValidationRules(): array
    {
        return ProfileDefinition::validationRules();
    }

    /**
     * The fields kept on the HR profile rather than on the employee itself.
     *
     * @return list<string>
     */
    public static function detailFields(): array
    {
        return ProfileDefinition::detailFields();
    }

    /**
     * Reading a profile field through the employee still works, so a view that wants
     * someone's phone number does not have to know where it is kept. Writing one does
     * not: use {@see saveProfile()}, so that it is always obvious when the profile is
     * being changed.
     */
    public function getAttribute($key)
    {
        if (! array_key_exists($key, $this->attributes)
            && ! $this->hasGetMutator($key)
            && ! $this->isRelation($key)
            && in_array($key, static::detailFields(), true)) {
            return $this->details->{$key};
        }

        return parent::getAttribute($key);
    }

    /**
     * Saves a flat set of attributes to wherever each one belongs. Fields the caller
     * did not mention are left alone, so a partial update stays partial.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveProfile(array $attributes): static
    {
        $detailFields = static::detailFields();

        $this->fill(Arr::except($attributes, $detailFields))->save();

        $profile = Arr::only($attributes, $detailFields);

        if ($profile !== []) {
            $this->details()->updateOrCreate([], $profile);
            $this->unsetRelation('details');
        }

        return $this;
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)->withPivot(['is_owner', 'is_here']);
    }

    public function sick_leaves()
    {
        return 10;
    }

    /**
     * @return BelongsToMany<Flight, $this>
     */
    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class);
    }

    public function getOwnRoomAttribute()
    {
        return $this->rooms()->where('is_owner', true)->exists();
    }

    public function getAdministrationNameAttribute()
    {
        return $this->department?->administration?->name;
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department?->name;
    }

    public function getLocationNameAttribute()
    {
        return $this->location?->name;
    }

    public function getCenterNameAttribute()
    {
        return $this->center?->name;
    }

    public function getStartDateAttribute($date)
    {
        if (is_null($date)) {
            return null;
        }

        return date('Y/m/d', strtotime($date));
    }

    public function getLastDateAttribute($date)
    {
        if (is_null($date)) {
            return null;
        }

        return date('Y/m/d', strtotime($date));
    }

    public function getBalanceAttribute()
    {
        return $this->total_balance;
    }

    /**
     * Recalculates the leave balance and stores it. Already whole days by the time
     * it arrives — see TimeSheetBuilder::roundToWholeDays().
     */
    public function calculateBalance(): void
    {
        $this->total_balance = TimeSheetBuilder::calculateBalance(
            $this->id,
            $this->schedule,
            $this->transfered_balance
        );

        $this->save();
    }

    public function getTotalWorkingDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['A', 'B', 'Y', 'K'])->count();
    }

    public function getTotalOffDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['F', 'X'])->count();
    }

    public function getDefaultOverTimeValueAttribute()
    {
        return 2;
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isFieldCoordinator(): bool
    {
        return $this->hasRole('fieldcoordinator');
    }

    public function isSuperintendent(): bool
    {
        return $this->hasRole('superintendent');
    }

    public function isTimekeeper(): bool
    {
        return $this->hasRole('timekeeper');
    }

    /**
     * @return BelongsToMany<ManagementScope, $this>
     */
    public function managementScopes(): BelongsToMany
    {
        return $this->belongsToMany(ManagementScope::class, 'management_scope_manager', 'manager_id', 'management_scope_id');
    }

    /**
     * Legacy owned scopes.
     */
    /**
     * @return HasMany<ManagementScope, $this>
     */
    public function ownedManagementScopes(): HasMany
    {
        return $this->hasMany(ManagementScope::class, 'manager_id');
    }

    public function getFullNameAttribute(): string
    {
        return (string) $this->english_name;
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Get all managed employees as a collection.
     */
    public function managedEmployees(?string $context = 'general')
    {
        return $this->managedEmployeesQuery($context)->get();
    }

    /**
     * Who this employee manages, under the original scope model. The rules live in
     * {@see ManagedEmployeeQuery}; this is how the rest of the application asks.
     *
     * @return Builder<Employee>
     */
    public function managedEmployeesQuery(?string $context = 'general'): Builder
    {
        return app(ManagedEmployeeQuery::class)->forEmployee($this, $context);
    }

    /**
     * True/false check if this employee can manage the target.
     */
    public function canManage(Employee $target): bool
    {
        if ($this->id === $target->id) {
            return false;
        }

        if ($this->trashed() || $target->trashed()) {
            return false;
        }

        if ($this->isArchived() || $target->isArchived()) {
            return false;
        }

        return $this
            ->managedEmployeesQuery()
            ->where('id', $target->id)
            ->exists();
    }

    //     foreach ($scopes as $scope) {
    //         if ($scope->matchesTargetEmployee($target)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

}
