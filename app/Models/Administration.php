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
class Administration extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'code', 'arabic_name'];

    protected $searchableFields = ['*'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
