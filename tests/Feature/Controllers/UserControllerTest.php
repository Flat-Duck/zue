<?php

namespace Tests\Feature\Controllers;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
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

    /**
     * The role checkboxes post ids, because that is what the form was built to
     * send. Spatie reads a string as a *name*, so "4" was looked up as a role
     * called "4" and assigning anybody a role threw RoleDoesNotExist.
     */
    #[Test]
    public function roles_are_assigned_from_the_ids_the_form_posts(): void
    {
        $user = User::factory()->create();

        $roles = Role::query()->whereIn('name', ['user', 'timekeeper', 'supervisor'])->get();

        $this->put(route('users.update', $user), [
            'name' => 'Ahmed Salem',
            'email' => 'ahmed@example.com',
            'roles' => $roles->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['user', 'timekeeper', 'supervisor'],
            $user->fresh()->getRoleNames()->all()
        );
    }

    #[Test]
    public function roles_are_assigned_the_same_way_when_the_user_is_created(): void
    {
        $role = Role::findByName('supervisor');

        $this->post(route('users.store'), [
            'employee_id' => Employee::factory()->create()->id,
            'name' => 'Ahmed Salem',
            'email' => 'new@example.com',
            'password' => 'a-long-enough-password',
            'roles' => [(string) $role->id],
        ])->assertRedirect();

        $this->assertSame(['supervisor'], User::query()->where('email', 'new@example.com')->sole()->getRoleNames()->all());
    }

    /**
     * `roles` was validated as `array` and nothing more, so a value that matched
     * no role reached Spatie and came back as a 500.
     */
    #[Test]
    public function a_role_that_does_not_exist_is_a_validation_error_not_a_crash(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();

        $this->put(route('users.update', $user), [
            'name' => 'Ahmed Salem',
            'email' => 'ahmed@example.com',
            'roles' => ['9999'],
        ])->assertSessionHasErrors('roles.0');

        $this->assertSame([], $user->fresh()->getRoleNames()->all());
    }

    #[Test]
    public function clearing_every_box_removes_every_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('supervisor');

        $this->put(route('users.update', $user), [
            'name' => 'Ahmed Salem',
            'email' => 'ahmed@example.com',
        ])->assertRedirect();

        $this->assertSame([], $user->fresh()->getRoleNames()->all());
    }
}
