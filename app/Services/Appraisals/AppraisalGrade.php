<?php

namespace App\Services\Appraisals;

final class AppraisalGrade
{
    public static function fromPercentage(?float $p): ?string
    {
        if ($p === null) {
            return null;
        }
        if ($p >= 90) {
            return 'ممتاز';
        }
        if ($p >= 80) {
            return 'جيد جداً';
        }
        if ($p >= 70) {
            return 'جيد';
        }
        if ($p >= 60) {
            return 'مقبول';
        }

        return 'ضعيف';
    }
}
