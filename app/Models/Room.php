<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['number', 'beds', 'residence_id'];

    protected $appends = ['available'];

    protected $searchableFields = ['*'];

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class)->withPivot(['is_owner', 'is_here']);
    }

    public function getAvailableAttribute()
    {
        $residents = $this->employees_count ?? $this->employees()->count();

        return $this->beds > $residents;
    }
}
