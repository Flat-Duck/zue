<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Employee|null $employee
 * @property-read Employee|null $revised_by
 * @property-read Employee|null $time_keeper
 * @property-read Employee|null $super_visor
 * @property-read Employee|null $super_intendent
 */
class TimeSheet extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'value',
        'day',
        'employee_id',
        'revised_at',
        'old_value',
        'user_id',
        'over_time',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'time_sheets';

    protected $casts = [
        'day' => 'date',
        'revised_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function time_keeper(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'timekeeper_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function super_intendent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'superintendent_id');
    }

    /**
     * The employee who revised this sheet.
     *
     * Carried over from the legacy system's `revised_by`, which held an
     * employee number.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function revised_by(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'admin_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function super_visor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getOtValueAttribute(): int
    {
        if (in_array($this->value, ['Y', 'B', 'K'])) {
            return 4;
        } elseif ($this->value === 'A') {
            return 2;
        }

        return 0;
    }

    public function getCssClassAttribute(): string
    {
        if (in_array($this->value, ['Y', 'B', 'K', 'A'])) {
            return 'skyblue';
        } elseif (in_array($this->value, ['F', 'X'])) {
            return 'grassgreen';
        }

        return '';
    }
}
