<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightStation extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'name_ar', 'code', 'is_field', 'is_active'];

    protected $searchableFields = ['*'];

    /**
     * How many route definitions reference this station.
     */
    public function routeLegsCount(): int
    {
        return FlightRouteLeg::query()
            ->where('from_station_id', $this->id)
            ->orWhere('to_station_id', $this->id)
            ->count();
    }

    /**
     * How many already-operated flight legs reference this station.
     */
    public function flightLegsCount(): int
    {
        return FlightLeg::query()
            ->where('from_station_id', $this->id)
            ->orWhere('to_station_id', $this->id)
            ->count();
    }

    /**
     * The name to print on an Arabic manifest, falling back to the English one.
     */
    public function displayNameAr(): string
    {
        return (string) ($this->name_ar ?: $this->name);
    }

    protected $casts = [
        'is_field' => 'boolean',
        'is_active' => 'boolean',
    ];
}
