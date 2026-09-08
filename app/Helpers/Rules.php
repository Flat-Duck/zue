<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;

class Rules
{
    /**
     * @return array<int, string>
     */
    public static function getLetters(User $user): array
    {
        return self::baseLetters()->values()->all();
    }

    /**
     * @return Collection<int, string>
     */
    private static function baseLetters(): Collection
    {
        return collect(range('A', 'Z'))
            ->diff(['H', 'M', 'R', 'U', 'V'])
            ->values();
    }
}
