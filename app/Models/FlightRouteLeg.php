<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightRouteLeg extends Model
{
    use HasFactory;

    public const DIRECTION_COMING = 'coming';

    public const DIRECTION_LEAVING = 'leaving';

    protected $fillable = [
        'flight_route_id',
        'sequence',
        'from_station_id',
        'to_station_id',
        'direction',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    /**
     * @return BelongsTo<FlightRoute, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(FlightRoute::class, 'flight_route_id');
    }

    /**
     * @return BelongsTo<FlightStation, $this>
     */
    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(FlightStation::class, 'from_station_id');
    }

    /**
     * @return BelongsTo<FlightStation, $this>
     */
    public function toStation(): BelongsTo
    {
        return $this->belongsTo(FlightStation::class, 'to_station_id');
    }
}
