<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    public const TYPE_GROUP = 'group';

    public const TYPE_LINK = 'link';

    public const TYPE_HEADER = 'header';

    public const TYPE_DIVIDER = 'divider';

    public const AUTH_NONE = 'none';

    public const AUTH_PERMISSION = 'permission';

    public const AUTH_GATE = 'gate';

    public const AUTH_POLICY = 'policy';

    protected $fillable = [
        'parent_id',
        'type',
        'label',
        'label_key',
        'icon',
        'route_name',
        'route_parameters',
        'authorization_type',
        'permission_name',
        'gate',
        'policy_ability',
        'policy_model',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'route_parameters' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<NavigationItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<NavigationItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /**
     * @param  Builder<NavigationItem>  $query
     * @return Builder<NavigationItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<NavigationItem>  $query
     * @return Builder<NavigationItem>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function title(): string
    {
        if ($this->label_key) {
            $translated = __($this->label_key);

            if ($translated !== $this->label_key) {
                return $translated;
            }
        }

        return $this->label ?: (string) $this->route_name;
    }

    public function isLink(): bool
    {
        return $this->type === self::TYPE_LINK;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isHeader(): bool
    {
        return $this->type === self::TYPE_HEADER;
    }

    public function isDivider(): bool
    {
        return $this->type === self::TYPE_DIVIDER;
    }
}
