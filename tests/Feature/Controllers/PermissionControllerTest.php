<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['email' => 'admin@admin.com']));

        $this->seed(PermissionsSeeder::class);

        $this->withoutExceptionHandling();
    }

    #[Test]
    public function it_displays_index_view_with_permissions(): void
    {
        $response = $this->get(route('permissions.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.permissions.index')
            ->assertViewHas('permissions');
    }

    #[Test]
    public function it_displays_create_view_for_permission(): void
    {
        $response = $this->get(route('permissions.create'));

        $response->assertOk()->assertViewIs('app.permissions.create');
    }

    #[Test]
    public function it_stores_the_permission(): void
    {
        $response = $this->post(route('permissions.store'), [
            'name' => 'list secretaries',
            'roles' => [],
        ]);

        $this->assertDatabaseHas('permissions', ['name' => 'list secretaries']);

        $permission = Permission::latest('id')->first();

        $response->assertRedirect(route('permissions.edit', $permission));
    }

    #[Test]
    public function it_displays_show_view_for_permission(): void
    {
        $permission = Permission::first();

        $response = $this->get(route('permissions.show', $permission));

        $response
            ->assertOk()
            ->assertViewIs('app.permissions.show')
            ->assertViewHas('permission');
    }

    #[Test]
    public function it_displays_edit_view_for_permission(): void
    {
        $permission = Permission::first();

        $response = $this->get(route('permissions.edit', $permission));

        $response
            ->assertOk()
            ->assertViewIs('app.permissions.edit')
            ->assertViewHas('permission');
    }

    #[Test]
    public function it_updates_the_permission(): void
    {
        $permission = Permission::first();

        $data = [
            'name' => 'list managers',
            'roles' => [],
        ];

        $response = $this->put(route('permissions.update', $permission), $data);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'list managers',
        ]);

        $response->assertRedirect(route('permissions.edit', $permission));
    }

    #[Test]
    public function it_deletes_the_permission(): void
    {
        $permission = Permission::first();

        $response = $this->delete(route('permissions.destroy', $permission));

        $response->assertRedirect(route('permissions.index'));

        $this->assertModelMissing($permission);
    }
}
