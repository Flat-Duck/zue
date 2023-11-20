<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['number', 'beds', 'residence_id'];

    protected $searchableFields = ['*'];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }
}
