<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->seed(PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    #[Test]
    public function it_displays_index_view_with_users(): void
    {
        $users = User::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('users.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.users.index')
            ->assertViewHas('users');
    }

    #[Test]
    public function it_displays_create_view_for_user(): void
    {
        $response = $this->get(route('users.create'));

        $response->assertOk()->assertViewIs('app.users.create');
    }

    #[Test]
    public function it_stores_the_user(): void
    {
        $data = User::factory()
            ->make()
            ->toArray();
        $data['password'] = \Str::random('8');

        $response = $this->post(route('users.store'), $data);

        unset($data['password']);
        unset($data['email_verified_at']);

        $this->assertDatabaseHas('users', $data);

        $user = User::where('email', $data['email'])->firstOrFail();

        $response->assertRedirect(route('users.edit', $user));
    }

    #[Test]
    public function it_displays_show_view_for_user(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user));

        $response
            ->assertOk()
            ->assertViewIs('app.users.show')
            ->assertViewHas('user');
    }

    #[Test]
    public function it_displays_edit_view_for_user(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response
            ->assertOk()
            ->assertViewIs('app.users.edit')
            ->assertViewHas('user');
    }

    #[Test]
    public function it_updates_the_user(): void
    {
        $user = User::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique->email(),
        ];

        $data['password'] = \Str::random('8');

        $response = $this->put(route('users.update', $user), $data);

        unset($data['password']);
        unset($data['email_verified_at']);

        $data['id'] = $user->id;

        $this->assertDatabaseHas('users', $data);

        $response->assertRedirect(route('users.edit', $user));
    }

    #[Test]
    public function it_deletes_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));

        $this->assertModelMissing($user);
    }
}
