<?php

namespace App\Models;

use Database\Factories\ScopePolicyCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One value a scope covers, in one dimension.
 *
 * Field, department and cost centre are three separate things: a department such
 * as Gas Plant has staff at both 103A and 103D, and cost centres cut across both.
 * A scope therefore says which values it covers in each dimension it cares about,
 * and says nothing at all about the dimensions it does not.
 */
class ScopePolicyCriterion extends Model
{
    /** @use HasFactory<ScopePolicyCriterionFactory> */
    use HasFactory;

    public const FIELD = 'field';

    public const DEPARTMENT = 'department';

    public const CENTER = 'center';

    public const EMPLOYEE = 'employee';

    /**
     * The three that narrow one another. `employee` is not among them: naming
     * someone adds them to the scope rather than filtering it.
     *
     * @var list<string>
     */
    public const FILTERING_DIMENSIONS = [self::FIELD, self::DEPARTMENT, self::CENTER];

    /**
     * The employee column each filtering dimension is matched against.
     *
     * @var array<string, string>
     */
    public const EMPLOYEE_COLUMNS = [
        self::FIELD => 'location_id',
        self::DEPARTMENT => 'department_id',
        self::CENTER => 'center_id',
    ];

    protected $fillable = [
        'policy_id',
        'dimension',
        'value_id',
    ];

    protected $casts = [
        'value_id' => 'int',
    ];

    /**
     * @return BelongsTo<ScopePolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(ScopePolicy::class, 'policy_id');
    }
}
