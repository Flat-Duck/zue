<?php

namespace Tests\Unit\Legacy;

use App\Services\Legacy\LegacyIdentityMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LegacyIdentityMapTest extends TestCase
{
    public function test_a_user_number_matches_the_employee_number(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 4001, number: 9094);

        $this->assertTrue($map->registerUser(legacyUserId: 77, legacyNumber: 9094, name: 'A', email: 'a@x.test'));
        $this->assertSame(4001, $map->employeeIdForUser(77));
        $this->assertSame(LegacyIdentityMap::MATCH_USER_NUMBER, $map->matchRankForUser(77));
    }

    public function test_a_null_user_number_falls_back_to_the_user_id_as_an_employee_number(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 4001, number: 9094);

        $this->assertTrue($map->registerUser(legacyUserId: 9094, legacyNumber: null, name: 'A', email: 'a@x.test'));
        $this->assertSame(4001, $map->employeeIdForUser(9094));
        $this->assertSame(LegacyIdentityMap::MATCH_USER_ID_AS_NUMBER, $map->matchRankForUser(9094));
    }

    /**
     * The one case in the real dump where the number is a dead end: the legacy row
     * took the employee's *id* as its key, and that employee's number differs.
     */
    public function test_the_user_id_falls_back_to_the_employee_id_when_no_number_matches(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 6718, number: 6716);

        $this->assertTrue($map->registerUser(legacyUserId: 6718, legacyNumber: 6718, name: 'A', email: 'a@x.test'));
        $this->assertSame(6718, $map->employeeIdForUser(6718));
        $this->assertSame(LegacyIdentityMap::MATCH_USER_ID_AS_EMPLOYEE_ID, $map->matchRankForUser(6718));
    }

    public function test_a_user_matching_no_employee_is_reported_and_not_resolved(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 4001, number: 9094);

        $this->assertFalse($map->registerUser(legacyUserId: 12, legacyNumber: 555, name: 'Ghost', email: 'g@x.test'));
        $this->assertNull($map->employeeIdForUser(12));
        $this->assertSame([[
            'user_id' => 12,
            'email' => 'g@x.test',
            'name' => 'Ghost',
            'number' => 555,
        ]], $map->unresolvedUsers());
    }

    #[DataProvider('conflictOrderings')]
    public function test_an_explicit_number_beats_an_id_guess_regardless_of_registration_order(callable $register): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 9676, number: 9676);

        $register($map);

        $this->assertSame(9676, $map->employeeIdForUser(10841), 'The user carrying an explicit number should hold the link.');
        $this->assertNull($map->employeeIdForUser(9676), 'The user matched only by its id should have been displaced.');

        $conflicts = $map->conflicts();
        $this->assertCount(1, $conflicts);
        $this->assertSame(9676, $conflicts[0]['employee_id']);
        $this->assertSame(10841, $conflicts[0]['kept_user_id']);
        $this->assertSame(9676, $conflicts[0]['dropped_user_id']);
        $this->assertSame('weak@x.test', $conflicts[0]['dropped_email']);
    }

    /**
     * @return array<string, array{0: callable(LegacyIdentityMap): void}>
     */
    public static function conflictOrderings(): array
    {
        $weak = static fn (LegacyIdentityMap $map) => $map->registerUser(9676, null, 'Weak', 'weak@x.test');
        $strong = static fn (LegacyIdentityMap $map) => $map->registerUser(10841, 9676, 'Strong', 'strong@x.test');

        return [
            'weak registered first' => [static function (LegacyIdentityMap $map) use ($weak, $strong): void {
                $weak($map);
                $strong($map);
            }],
            'strong registered first' => [static function (LegacyIdentityMap $map) use ($weak, $strong): void {
                $strong($map);
                $weak($map);
            }],
        ];
    }

    public function test_equal_matches_are_decided_by_the_later_row(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 500, number: 500);

        $map->registerUser(legacyUserId: 11, legacyNumber: 500, name: 'Older', email: 'o@x.test');
        $map->registerUser(legacyUserId: 22, legacyNumber: 500, name: 'Newer', email: 'n@x.test');

        $this->assertSame(500, $map->employeeIdForUser(22));
        $this->assertNull($map->employeeIdForUser(11));
    }

    public function test_a_reviser_number_translates_to_the_employee_id(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 6718, number: 6716);
        $map->registerEmployee(id: 9094, number: 9094);

        $this->assertSame(6718, $map->employeeIdForNumber(6716));
        $this->assertSame(9094, $map->employeeIdForNumber(9094));
        $this->assertNull($map->employeeIdForNumber(424242));
        $this->assertNull($map->employeeIdForNumber(null));
    }

    /**
     * A number that is nobody's number but happens to be somebody's id still points
     * at a real person, so it resolves rather than being thrown away.
     */
    public function test_a_reviser_number_falls_back_to_a_matching_employee_id(): void
    {
        $map = new LegacyIdentityMap;
        $map->registerEmployee(id: 6718, number: 6716);

        $this->assertSame(6718, $map->employeeIdForNumber(6718));
    }
}
