<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $code
 * @property string|null $arabic_name
 * @property int|null $administration_id
 * @property-read Administration|null $administration
 */
class Department extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'code', 'arabic_name', 'administration_id'];

    protected $searchableFields = ['*'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }
}
