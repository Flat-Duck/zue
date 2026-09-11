<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The endpoints somebody would hammer.
 *
 * Login had a limit already. The password-reset form did not, so anybody could
 * make the server send a stranger a reset email as fast as they could post — and
 * the import endpoints, each of which parses a spreadsheet and writes hundreds of
 * rows, could be called in a loop by any signed-in user with the permission.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login');
    }

    #[Test]
    public function login_locks_out_after_five_wrong_passwords(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-horse')]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-horse']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
        // The right password does not get in during the lockout.
        $this->assertGuest();
    }

    #[Test]
    public function asking_for_a_password_reset_is_limited_per_address(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), ['email' => 'someone@example.com'])
                ->assertStatus(302);
        }

        $this->post(route('password.email'), ['email' => 'someone@example.com'])
            ->assertStatus(429);
    }

    #[Test]
    public function submitting_a_new_password_is_limited_too(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.update'), [
                'token' => 'not-a-real-token',
                'email' => 'someone@example.com',
                'password' => 'new-password-here',
                'password_confirmation' => 'new-password-here',
            ])->assertStatus(302);
        }

        $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => 'someone@example.com',
            'password' => 'new-password-here',
            'password_confirmation' => 'new-password-here',
        ])->assertStatus(429);
    }

    /**
     * @return list<string>
     */
    public static function importRoutes(): array
    {
        return [
            'users.import',
            'employees.import-profiles',
            'employees.import-archived-employees',
            'maintenance.import',
        ];
    }

    #[Test]
    public function every_import_endpoint_is_limited_per_user(): void
    {
        $this->seed(PermissionsSeeder::class);

        // One budget covers every import a person makes, so each route gets its
        // own person here.
        foreach (self::importRoutes() as $route) {
            $admin = User::factory()->create();
            $admin->assignRole('super-admin');

            $file = UploadedFile::fake()->create('nothing.xlsx', 1);

            for ($attempt = 1; $attempt <= 6; $attempt++) {
                $status = $this->actingAs($admin)->post(route($route), ['file' => $file])->status();

                $this->assertNotSame(429, $status, "{$route} refused attempt {$attempt}, before the limit.");
            }

            $this->actingAs($admin)
                ->post(route($route), ['file' => $file])
                ->assertStatus(429, "{$route} accepted a seventh call within the minute.");
        }
    }

    #[Test]
    public function restoring_a_backup_is_limited_per_user(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($admin)->post(route('maintenance.restore', 'nothing.sql'));
        }

        $this->actingAs($admin)
            ->post(route('maintenance.restore', 'nothing.sql'))
            ->assertStatus(429);
    }

    /**
     * Re-entering a password for a sensitive action is a password guess with a
     * stolen session, so it gets the same budget as a reset.
     */
    #[Test]
    public function confirming_a_password_is_limited(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-horse')]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($user)->post(route('password.confirm'), ['password' => 'wrong'])->assertStatus(302);
        }

        $this->actingAs($user)->post(route('password.confirm'), ['password' => 'correct-horse'])->assertStatus(429);
    }
}
