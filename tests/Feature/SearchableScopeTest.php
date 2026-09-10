<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `Searchable` used to define `withArchived()` and `withoutArchived()` alongside
 * `SoftArchivingScope`, which registers builder macros of the same names.
 *
 * A macro takes precedence over a local scope, so on `Employee` — the only model
 * with both — the trait's versions never ran. On the fourteen models that use
 * `Searchable` without `SoftArchives`, the trait's versions did run, and filtered on
 * an `archived_at` column those tables do not have. They are gone; these tests hold
 * both halves of that.
 */
class SearchableScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function archived_employees_are_hidden_by_default_and_reachable_on_request(): void
    {
        Employee::factory()->create(['archived_at' => null]);
        Employee::factory()->create(['archived_at' => now()->subYear()]);

        $this->assertSame(1, Employee::query()->count());
        $this->assertSame(2, Employee::withArchived()->count());
        $this->assertSame(1, Employee::onlyArchived()->count());
        $this->assertSame(1, Employee::withoutArchived()->count());
    }

    /**
     * A model with no `archived_at` must not inherit an archive filter it cannot
     * satisfy. This used to raise "Unknown column 'archived_at'".
     */
    #[Test]
    public function a_model_without_an_archived_column_has_no_archive_scope(): void
    {
        Center::factory()->count(3)->create();

        $this->assertFalse(
            Center::query()->hasNamedScope('withArchived'),
            'Centres have no archived_at column and must not carry an archive scope.'
        );
        $this->assertFalse(Center::query()->hasNamedScope('withoutArchived'));

        // The search the index screens actually use still works.
        $this->assertSame(3, Center::search('')->count());
    }

    #[Test]
    public function searching_matches_on_any_searchable_column(): void
    {
        Center::factory()->create(['name' => 'Gas Plant']);
        Center::factory()->create(['name' => 'Drilling']);

        $this->assertSame(1, Center::search('Gas')->count());
        $this->assertSame(2, Center::search('')->count());
    }
}
