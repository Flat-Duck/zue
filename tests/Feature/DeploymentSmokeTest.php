<?php

namespace Tests\Feature;

use App\Livewire\TimeTable;
use App\Models\Administration;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\BuildsScopes;
use Tests\TestCase;

/**
 * End-to-end smoke coverage for a deployed application.
 *
 * These tests answer one question: after a deploy, does the application still
 * work for a real person? They walk the path a user actually takes — sign in,
 * land on the dashboard, open a Livewire page, get refused where they should
 * be, write something, sign out — rather than exercising units in isolation.
 */
class DeploymentSmokeTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase;

    private const PASSWORD = 'smoke-test-password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    private function user(array $permissions = []): User
    {
        $user = User::factory()->create([
            'password' => Hash::make(self::PASSWORD),
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * A user linked to an employee holding a company-wide time_sheet scope.
     */
    private function timesheetManager(): User
    {
        $user = $this->user([
            'list timesheets',
            'view timesheets',
            'fill timesheets',
            'list employees',
            'view employees',
        ]);

        $this->buildGlobalScope([$user->employee]);

        return $user;
    }

    #[Test]
    public function the_login_page_is_reachable(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    #[Test]
    public function valid_credentials_start_an_authenticated_session(): void
    {
        $user = $this->user();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function invalid_credentials_are_rejected(): void
    {
        $user = $this->user();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function the_dashboard_renders_for_an_authenticated_user(): void
    {
        $this->actingAs($this->user())
            ->get('/home')
            ->assertOk();
    }

    #[Test]
    public function a_livewire_page_renders_with_its_components_mounted(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->timesheetManager())
            ->get(route('time-sheets.fill', $employee))
            ->assertOk()
            ->assertSeeLivewire(TimeTable::class);
    }

    #[Test]
    public function guests_are_redirected_to_login_from_protected_pages(): void
    {
        $this->get(route('maintenance.index'))->assertRedirect(route('login'));
        $this->get(route('time-sheets.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_ordinary_user_is_refused_maintenance_operations(): void
    {
        $this->actingAs($this->user())
            ->get(route('maintenance.index'))
            ->assertForbidden();
    }

    #[Test]
    public function registration_stays_disabled(): void
    {
        $this->post('/register', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'password-1234',
            'password_confirmation' => 'password-1234',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
    }

    #[Test]
    public function an_authorised_user_can_complete_a_crud_write(): void
    {
        $user = $this->user([
            'list departments',
            'view departments',
            'create departments',
        ]);

        $administration = Administration::factory()->create();

        $response = $this->actingAs($user)->post(route('departments.store'), [
            'name' => 'Smoke Test Department',
            'administration_id' => $administration->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'name' => 'Smoke Test Department',
        ]);
    }

    #[Test]
    public function an_unauthorised_user_cannot_complete_that_same_write(): void
    {
        $this->actingAs($this->user())
            ->post(route('departments.store'), [
                'name' => 'Should Not Exist',
                'administration_id' => Administration::factory()->create()->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['name' => 'Should Not Exist']);
    }

    #[Test]
    public function logging_out_ends_the_session(): void
    {
        $this->actingAs($this->user())
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }

    /**
     * With debugging off, an error page says nothing about the code. Ignition's
     * routes — one of which executes code — are a dev dependency and are absent
     * from a `--no-dev` install, but they must also be inert wherever they exist
     * and debugging is off.
     */
    #[Test]
    public function with_debugging_off_an_error_reveals_nothing_and_the_debug_routes_are_inert(): void
    {
        config(['app.debug' => false]);

        Route::get('/_smoke/explode', function (): never {
            throw new \RuntimeException('secret-detail-that-must-not-leak');
        });

        $response = $this->get('/_smoke/explode');

        $response->assertStatus(500);
        $this->assertStringNotContainsString('secret-detail-that-must-not-leak', $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', $response->getContent());
        $this->assertStringNotContainsString(base_path(), $response->getContent());

        foreach (['/_ignition/health-check', '/_ignition/execute-solution', '/_ignition/update-config'] as $uri) {
            $status = $this->post($uri)->status();

            $this->assertContains($status, [403, 404, 405], "{$uri} answered {$status} with debugging off.");
        }
    }
}
