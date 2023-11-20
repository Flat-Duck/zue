<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flight extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['type', 'date', 'time'];

    protected $searchableFields = ['*'];

    protected $casts = [
        'date' => 'date',
    ];

    public function passengers()
    {
        return $this->belongsToMany(Passenger::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }
}
