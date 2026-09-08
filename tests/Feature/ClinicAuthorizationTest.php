<?php

namespace Tests\Feature;

use App\Livewire\ClinicEmployeeInfo;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_clinic(): void
    {
        $response = $this->get(route('clinic.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_clinic_permission_is_denied_from_employee_listing(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('clinic.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_clinic_permission_is_denied_from_diagnosis(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('clinic.diagnosis', $employee));

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_clinic_permission_cannot_store_clinical_exam(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('clinic.store'), [
            'name' => 'Test Patient',
            'number' => '123456',
        ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_clinic_permission_cannot_mount_clinic_livewire_component(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->create());

        Livewire::test(ClinicEmployeeInfo::class, ['employee' => $employee])
            ->assertForbidden();
    }
}
