<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Passenger extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'company', 'number', 'nationality'];

    protected $searchableFields = ['*'];

    public function flights()
    {
        return $this->belongsToMany(Flight::class);
    }
}
