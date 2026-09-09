<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The rooms import endpoint (`rr`) is the target of the rooms create form.
 *
 * It previously imported the file and then rendered the timesheet approval
 * view with four undefined variables, so a successful import ended in a 500.
 */
class RoomImportRouteTest extends TestCase
{
    use RefreshDatabase;

    private function maintainer(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('maintenance', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('maintenance');

        return $user;
    }

    #[Test]
    public function a_guest_cannot_import_rooms(): void
    {
        $this->post(route('rr'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_ordinary_user_cannot_import_rooms(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('rr'))
            ->assertForbidden();
    }

    #[Test]
    public function a_successful_import_redirects_back_instead_of_erroring(): void
    {
        ExcelFacade::fake();

        $response = $this->actingAs($this->maintainer())
            ->from(route('rooms.index'))
            ->post(route('rr'), [
                'rooms' => UploadedFile::fake()->create('rooms.xlsx', 16),
            ]);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function a_missing_file_is_rejected_by_validation(): void
    {
        $response = $this->actingAs($this->maintainer())
            ->from(route('rooms.index'))
            ->post(route('rr'), []);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHasErrors('rooms');
    }
}
