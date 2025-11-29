<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plane extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'capacity', 'lines'];

    protected $searchableFields = ['*'];

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }
}
