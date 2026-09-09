<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read FlightLeg|null $leg
 * @property-read Model|null $bookable
 * @property-read User|null $bookedBy
 */
class FlightBooking extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_WAITLISTED = 'waitlisted';

    protected $fillable = [
        'flight_leg_id',
        'bookable_type',
        'bookable_id',
        'status',
        'sequence',
        'booked_by_user_id',
        'note',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function leg(): BelongsTo
    {
        return $this->belongsTo(FlightLeg::class, 'flight_leg_id');
    }

    /**
     * The traveller: an Employee or a Passenger.
     */
    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isWaitlisted(): bool
    {
        return $this->status === self::STATUS_WAITLISTED;
    }

    /**
     * A display name that works for either kind of traveller.
     */
    public function travellerName(): string
    {
        $traveller = $this->bookable;

        if ($traveller instanceof Employee) {
            return trim((string) ($traveller->english_name ?: $traveller->number));
        }

        if ($traveller instanceof Passenger) {
            return trim((string) $traveller->name);
        }

        return '';
    }
}
