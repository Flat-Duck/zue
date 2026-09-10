<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plane extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'capacity', 'lines'];

    protected $searchableFields = ['*'];

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
