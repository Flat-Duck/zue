<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Flight|null $flight
 * @property-read FlightStation $fromStation
 * @property-read FlightStation $toStation
 * @property-read Collection<int, FlightBooking> $bookings
 * @property-read Collection<int, FlightBooking> $confirmedBookings
 * @property-read Collection<int, FlightBooking> $waitlistedBookings
 */
class FlightLeg extends Model
{
    use HasFactory;

    public const DIRECTION_COMING = 'coming';

    public const DIRECTION_LEAVING = 'leaving';

    protected $fillable = [
        'flight_id',
        'sequence',
        'from_station_id',
        'to_station_id',
        'direction',
        'seat_capacity',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'seat_capacity' => 'integer',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(FlightStation::class, 'from_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(FlightStation::class, 'to_station_id');
    }

    /**
     * @return HasMany<FlightBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(FlightBooking::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<FlightBooking, $this>
     */
    public function confirmedBookings(): HasMany
    {
        return $this->bookings()->where('status', FlightBooking::STATUS_CONFIRMED);
    }

    /**
     * @return HasMany<FlightBooking, $this>
     */
    public function waitlistedBookings(): HasMany
    {
        return $this->bookings()->where('status', FlightBooking::STATUS_WAITLISTED);
    }

    public function isComing(): bool
    {
        return $this->direction === self::DIRECTION_COMING;
    }

    /**
     * Seats still available on this leg. Never negative: downgrading the
     * aircraft can leave more confirmed travellers than seats, which is a
     * dispatcher problem to resolve, not a negative number to propagate.
     */
    public function seatsRemaining(): int
    {
        $confirmed = $this->relationLoaded('confirmedBookings')
            ? $this->confirmedBookings->count()
            : $this->confirmedBookings()->count();

        return max(0, $this->seat_capacity - $confirmed);
    }

    public function isOverCapacity(): bool
    {
        $confirmed = $this->relationLoaded('confirmedBookings')
            ? $this->confirmedBookings->count()
            : $this->confirmedBookings()->count();

        return $confirmed > $this->seat_capacity;
    }

    public function label(): string
    {
        // Both station foreign keys are NOT NULL, so neither side can be missing.
        return trim(sprintf(
            '%s → %s',
            $this->fromStation->code,
            $this->toStation->code
        ));
    }
}
