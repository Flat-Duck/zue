<?php

namespace Tests\Feature;

use App\Models\ScopeContext;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\BuildsScopes;
use Tests\TestCase;

/**
 * Managing the contexts a scope can exist for.
 *
 * Three of them are asked for by name in the code — time sheets, dispatch,
 * general — and the screen must not let anybody pull those out from under it.
 * Everything else is the owner's to add, rename and retire.
 */
class ScopeContextCrudTest extends TestCase
{
    use BuildsScopes;
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        if ($this->admin === null) {
            $this->seed(PermissionsSeeder::class);

            $this->admin = User::factory()->create();
            $this->admin->assignRole('super-admin');
        }

        return $this->admin;
    }

    #[Test]
    public function the_index_lists_every_context_with_its_scope_count(): void
    {
        $this->scopeContext(ScopeContext::TIME_SHEET);
        $training = ScopeContext::query()->create(['key' => 'training', 'name' => 'Training']);
        $this->buildScope('One', ['employee' => [1]], [], 'training');

        $this->actingAs($this->admin())
            ->get(route('scope-contexts.index'))
            ->assertOk()
            ->assertSee('time_sheet')
            ->assertSee('Training')
            ->assertSee(__('scopes.contexts_scope_count', ['count' => 1]));
    }

    #[Test]
    public function a_new_context_can_be_added(): void
    {
        $this->actingAs($this->admin())
            ->post(route('scope-contexts.store'), [
                'key' => 'overtime',
                'name' => 'Overtime',
                'name_ar' => 'العمل الإضافي',
                'carves_out_managers' => '1',
                'is_active' => '1',
                'sort_order' => '4',
            ])
            ->assertRedirect(route('scope-contexts.index'));

        $context = ScopeContext::query()->where('key', 'overtime')->sole();

        $this->assertSame('Overtime', $context->name);
        $this->assertSame('العمل الإضافي', $context->name_ar);
        $this->assertTrue($context->carves_out_managers);
        $this->assertSame(4, $context->sort_order);
    }

    #[Test]
    public function the_key_must_be_a_slug_and_unique(): void
    {
        ScopeContext::query()->create(['key' => 'training', 'name' => 'Training']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('scope-contexts.store'), ['key' => 'Training Days', 'name' => 'X'])
            ->assertSessionHasErrors('key');

        $this->actingAs($admin)
            ->post(route('scope-contexts.store'), ['key' => 'training', 'name' => 'X'])
            ->assertSessionHasErrors('key');
    }

    #[Test]
    public function a_context_can_be_renamed_but_its_key_cannot_change(): void
    {
        $context = ScopeContext::query()->create(['key' => 'training', 'name' => 'Training']);

        $this->actingAs($this->admin())
            ->put(route('scope-contexts.update', $context), ['name' => 'Staff training', 'is_active' => '0'])
            ->assertRedirect(route('scope-contexts.index'));

        $this->assertSame('Staff training', $context->fresh()->name);
        $this->assertFalse($context->fresh()->is_active);

        $this->actingAs($this->admin())
            ->put(route('scope-contexts.update', $context), ['key' => 'courses', 'name' => 'Training'])
            ->assertSessionHasErrors('key');

        $this->assertSame('training', $context->fresh()->key);
    }

    #[Test]
    public function a_built_in_context_cannot_be_deleted(): void
    {
        $timesheet = $this->scopeContext(ScopeContext::TIME_SHEET);

        $this->actingAs($this->admin())
            ->delete(route('scope-contexts.destroy', $timesheet))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($timesheet, 'Even a super admin cannot delete a context the code asks for by name.');
    }

    #[Test]
    public function a_context_with_scopes_cannot_be_deleted(): void
    {
        $training = ScopeContext::query()->create(['key' => 'training', 'name' => 'Training']);
        $this->buildScope('One', ['employee' => [1]], [], 'training');

        $this->actingAs($this->admin())
            ->delete(route('scope-contexts.destroy', $training))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($training);
    }

    #[Test]
    public function an_unused_custom_context_can_be_deleted(): void
    {
        $training = ScopeContext::query()->create(['key' => 'training', 'name' => 'Training']);

        $this->actingAs($this->admin())
            ->delete(route('scope-contexts.destroy', $training))
            ->assertRedirect(route('scope-contexts.index'));

        $this->assertModelMissing($training);
    }

    #[Test]
    public function someone_without_the_permission_is_refused(): void
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('scope-contexts.index'))->assertForbidden();
        $this->actingAs($user)->post(route('scope-contexts.store'), ['key' => 'x', 'name' => 'X'])->assertForbidden();
    }
}
