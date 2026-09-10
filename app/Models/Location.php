<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $code
 * @property string|null $arabic_name
 */
class Location extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'code', 'arabic_name', 'description'];

    protected $searchableFields = ['*'];

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
