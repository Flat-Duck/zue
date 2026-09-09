<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, FlightRouteLeg> $legs
 * @property-read Collection<int, Flight> $flights
 */
class FlightRoute extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'is_active'];

    protected $searchableFields = ['*'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function legs(): HasMany
    {
        return $this->hasMany(FlightRouteLeg::class)->orderBy('sequence');
    }

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
