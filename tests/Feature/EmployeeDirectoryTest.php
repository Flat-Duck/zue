<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The directory card came from Tabler's demo, which meant every employee in the
 * company was shown with the same initials and the same job title. It read as real
 * data, which is what made it worth a test rather than just a fix.
 */
class EmployeeDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    #[Test]
    public function the_card_shows_each_employees_own_job_title(): void
    {
        $employee = Employee::factory()->create([
            'english_name' => 'AHMED SALEM',
            'job_title_en' => 'Reservoir Engineer',
        ]);

        $this->actingAs($this->admin())
            ->get(route('employees.dir'))
            ->assertOk()
            ->assertSee('Reservoir Engineer')
            ->assertDontSee('Nuclear Power Engineer');

        $this->assertSame('AS', $employee->initials);
    }

    #[Test]
    public function initials_come_from_the_name_and_survive_odd_ones(): void
    {
        $this->assertSame('AS', Employee::factory()->make(['english_name' => 'ahmed salem'])->initials);
        $this->assertSame('A', Employee::factory()->make(['english_name' => 'Ahmed'])->initials);
        $this->assertSame('—', Employee::factory()->make(['english_name' => '', 'arabic_name' => ''])->initials);
        $this->assertSame('اس', Employee::factory()->make(['english_name' => '', 'arabic_name' => 'احمد سالم'])->initials);
    }
}
