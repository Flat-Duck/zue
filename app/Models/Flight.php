<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Plane $plane
 * @property-read FlightRoute|null $route
 * @property-read Collection<int, FlightLeg> $legs
 * @property-read Collection<int, FlightLeg> $comingLegs
 * @property-read Collection<int, FlightLeg> $leavingLegs
 */
class Flight extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['type', 'date', 'time', 'plane_id', 'flight_route_id'];

    protected $searchableFields = ['*'];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
    ];

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Passenger::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class);
    }

    public function plane(): BelongsTo
    {
        return $this->belongsTo(Plane::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FlightRoute::class, 'flight_route_id');
    }

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
