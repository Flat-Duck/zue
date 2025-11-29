<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flight extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['type', 'date', 'time','plane_id'];

    protected $searchableFields = ['*'];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
    ];

    public function passengers()
    {
        return $this->belongsToMany(Passenger::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }
    public function plane()
    {
        return $this->belongsTo(Plane::class);
    }
}
