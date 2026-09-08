<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Residence extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'type', 'location_id'];

    protected $searchableFields = ['*'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
