<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Residence extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'type'];

    protected $searchableFields = ['*'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
