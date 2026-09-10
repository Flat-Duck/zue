<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['number', 'beds', 'residence_id'];

    protected $appends = ['available'];

    protected $searchableFields = ['*'];

    /**
     * @return BelongsTo<Residence, $this>
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withPivot(['is_owner', 'is_here']);
    }

    public function getAvailableAttribute()
    {
        $residents = $this->employees_count ?? $this->employees()->count();

        return $this->beds > $residents;
    }
}
