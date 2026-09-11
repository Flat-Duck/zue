<?php

namespace Tests\Unit;

use App\Services\Appraisals\AppraisalGrade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AppraisalGradeTest extends TestCase
{
    #[DataProvider('grades')]
    public function test_grade_boundaries(?float $percentage, ?string $expected): void
    {
        $this->assertSame($expected, AppraisalGrade::fromPercentage($percentage));
    }

    public static function grades(): array
    {
        return [[null, null], [0, 'ضعيف'], [59.99, 'ضعيف'], [60, 'مقبول'],
            [69.99, 'مقبول'], [70, 'جيد'], [79.99, 'جيد'], [80, 'جيد جداً'],
            [89.99, 'جيد جداً'], [90, 'ممتاز'], [100, 'ممتاز']];
    }
}
