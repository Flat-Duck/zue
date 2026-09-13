<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Plane $plane
 * @property-read FlightRoute|null $route
 * @property-read Collection<int, FlightLeg> $legs
 * @property-read Collection<int, FlightLeg> $comingLegs
 * @property Carbon|null $registration_opens_at
 * @property Carbon|null $registration_closes_at
 * @property-read Collection<int, FlightLeg> $leavingLegs
 */
class Flight extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'type',
        'date',
        'time',
        'plane_id',
        'flight_route_id',
        'registration_opens_at',
        'registration_closes_at',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
    ];

    public function registrationIsOpen(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->registration_opens_at === null || $this->registration_closes_at === null) {
            return false;
        }

        return $at->betweenIncluded($this->registration_opens_at, $this->registration_closes_at);
    }

    public function registrationHasClosed(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->registration_closes_at !== null && $at->greaterThan($this->registration_closes_at);
    }

    /**
     * @return BelongsToMany<Passenger, $this>
     */
    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Passenger::class);
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class);
    }

    /**
     * @return BelongsTo<Plane, $this>
     */
    public function plane(): BelongsTo
    {
        return $this->belongsTo(Plane::class);
    }

    /**
     * @return BelongsTo<FlightRoute, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(FlightRoute::class, 'flight_route_id');
    }

    /**
     * @return HasMany<FlightLeg, $this>
     */
    public function legs(): HasMany
    {
        return $this->hasMany(FlightLeg::class)->orderBy('sequence');
    }

    /**
     * Legs flying toward a field, whose passengers are arriving.
     */
    public function comingLegs(): HasMany
    {
        return $this->legs()->where('direction', FlightLeg::DIRECTION_COMING);
    }

    /**
     * Legs flying away from a field, whose passengers are departing.
     */
    public function leavingLegs(): HasMany
    {
        return $this->legs()->where('direction', FlightLeg::DIRECTION_LEAVING);
    }
}
