<?php

namespace App\Models;

use Database\Factories\ScopeContextFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Why a management scope exists.
 *
 * The same person legitimately manages different people for different purposes: a
 * supervisor fills time sheets for their own department, and the same supervisor,
 * acting as dispatcher, books travellers out of two fields at once. The context is
 * what keeps those two answers apart, so nothing may assume that one person's
 * scope is the same across the application.
 *
 * It is a record rather than a constant because the list is expected to grow.
 */
class ScopeContext extends Model
{
    /** @use HasFactory<ScopeContextFactory> */
    use HasFactory;

    public const TIME_SHEET = 'time_sheet';

    public const DISPATCHER = 'dispatcher';

    public const GENERAL = 'general';

    protected $fillable = [
        'key',
        'name',
        'name_ar',
        'carves_out_managers',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'carves_out_managers' => 'bool',
        'is_active' => 'bool',
        'sort_order' => 'int',
    ];

    /**
     * @return HasMany<ScopePolicy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(ScopePolicy::class, 'context_id');
    }

    public function label(): string
    {
        return app()->getLocale() === 'ar' && filled($this->name_ar)
            ? (string) $this->name_ar
            : (string) $this->name;
    }
}
