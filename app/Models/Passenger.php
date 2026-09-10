<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Passenger extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'company', 'number', 'nationality'];

    protected $searchableFields = ['*'];

    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class);
    }
}
