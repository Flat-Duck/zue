<?php

namespace Tests\Feature;

use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The HR profile has to be visible on the employee page and editable through
 * the form, from a single definition so the two cannot drift apart.
 */
class EmployeeProfileFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['email' => 'admin@admin.com']));
        $this->seed(PermissionsSeeder::class);
    }

    /**
     * Every field that is actually stored on the employee.
     *
     * Administration is excluded: it follows the department rather than being a
     * column, so it is shown but never submitted.
     *
     * @return list<string>
     */
    private function storedFieldNames(): array
    {
        return collect(Employee::profileSections())
            ->flatMap(fn (array $section): array => collect($section['fields'])
                ->reject(fn (array $field): bool => $field['type'] === 'administration')
                ->keys()
                ->all())
            ->all();
    }

    #[Test]
    public function the_definition_covers_every_section(): void
    {
        $sections = Employee::profileSections();

        $this->assertNotEmpty($sections);

        // Work details come first because the rest of the system depends on them.
        $this->assertSame('Work', array_key_first($sections));

        foreach (['Work', 'Identity', 'Job details', 'Payroll', 'Documents', 'Education', 'Banking'] as $expected) {
            $this->assertArrayHasKey($expected, $sections);
        }

        // The fields the owner asked to lead with.
        $work = array_keys($sections['Work']['fields']);
        $this->assertSame(
            ['number', 'english_name', 'arabic_name', 'location_id', 'administration_id', 'department_id', 'center_id', 'schedule'],
            $work
        );
    }

    #[Test]
    public function every_profile_field_is_fillable_and_has_a_column(): void
    {
        $employee = new Employee;

        foreach ($this->storedFieldNames() as $name) {
            $this->assertContains($name, $employee->getFillable(), "[{$name}] is not fillable.");
            $this->assertTrue(
                Schema::hasColumn('employees', $name),
                "[{$name}] has no database column."
            );
        }
    }

    #[Test]
    public function every_profile_field_is_validated(): void
    {
        $rules = Employee::profileValidationRules();

        foreach ($this->storedFieldNames() as $name) {
            $this->assertArrayHasKey($name, $rules, "[{$name}] has no validation rule.");
        }
    }

    #[Test]
    public function the_administration_is_shown_but_follows_the_department(): void
    {
        $administration = Administration::factory()->create(['name' => 'Operations Administration']);
        $department = Department::factory()->create(['administration_id' => $administration->id]);
        $employee = Employee::factory()->create(['department_id' => $department->id]);

        // Shown on the record...
        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Operations Administration');

        // ...offered on the form as a filter, but never stored on the employee.
        $this->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('Administration')
            ->assertSee('Filters the department list');

        $this->assertFalse(
            Schema::hasColumn('employees', 'administration_id'),
            'Administration should not be a column on employees.'
        );
    }

    #[Test]
    public function the_show_page_displays_the_profile(): void
    {
        $employee = Employee::factory()->create([
            'arabic_name' => 'صالح خليفة',
            'nationality' => 'ليبي',
            'basic_salary' => 5797,
            'bank_name' => 'مصرف الوحدة',
            'education_level' => 'ثـانــوي',
        ]);

        $response = $this->get(route('employees.show', $employee))->assertOk();

        $response->assertSee('صالح خليفة', false)
            ->assertSee('ليبي', false)
            ->assertSee('مصرف الوحدة', false)
            ->assertSee('ثـانــوي', false)
            // Section headings and the Arabic source labels are shown.
            ->assertSee('Identity')
            ->assertSee('Payroll')
            ->assertSee('الجنسية', false);
    }

    #[Test]
    public function the_show_page_renders_the_profile_read_only(): void
    {
        $employee = Employee::factory()->create(['arabic_name' => 'اسم']);

        $html = $this->get(route('employees.show', $employee))->getContent();

        // The profile inputs on the show page must not be editable.
        $this->assertMatchesRegularExpression(
            '/id="arabic_name"[^>]*disabled/s',
            $html,
            'Profile fields on the show page should be disabled.'
        );
    }

    #[Test]
    public function the_edit_form_offers_every_profile_field(): void
    {
        $employee = Employee::factory()->create();

        $html = $this->get(route('employees.edit', $employee))->getContent();

        foreach ($this->storedFieldNames() as $name) {
            $this->assertStringContainsString(
                'name="'.$name.'"',
                $html,
                "[{$name}] is missing from the edit form."
            );
        }
    }

    #[Test]
    public function the_create_form_offers_every_profile_field(): void
    {
        $html = $this->get(route('employees.create'))->getContent();

        foreach ($this->storedFieldNames() as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $html, "[{$name}] is missing from the create form.");
        }
    }

    /**
     * The create and edit pages present the same card sections as the show
     * page. The form used to be a card itself, which buried those sections in
     * a narrow column wrapper.
     */
    #[Test]
    public function the_edit_and_create_pages_use_the_same_card_layout_as_the_view(): void
    {
        $employee = Employee::factory()->create();

        foreach ([route('employees.edit', $employee), route('employees.create')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString(
                '<form method="POST" action="'.$url.'" class="card"',
                $html,
                'The form should not be a card; the sections provide their own.'
            );

            $this->assertStringNotContainsString('row g-5', $html, 'The old column wrapper should be gone.');

            // Section headings, exactly as the show page renders them.
            $this->assertStringContainsString('Work', $html);
            $this->assertStringContainsString('بيانات العمل', $html);
        }
    }

    #[Test]
    public function profile_fields_are_saved_when_the_employee_is_updated(): void
    {
        $employee = Employee::factory()->create();

        $payload = [
            'user_id' => $employee->user_id,
            'location_id' => $employee->location_id,
            'department_id' => $employee->department_id,
            'center_id' => $employee->center_id,

            'arabic_name' => 'صالح خليفة مشري',
            'nationality' => 'ليبي',
            'gender' => 'ذكر',
            'birth_date' => '1962-07-01',
            'basic_salary' => '5797',
            'total_salary' => '6097',
            'children_count' => '8',
            'bank_name' => 'مصرف الوحدة',
            'hr_notes' => 'Imported from the personnel export.',
        ];

        $this->put(route('employees.update', $employee), $payload)->assertRedirect();

        $employee->refresh();

        $this->assertSame('صالح خليفة مشري', $employee->arabic_name);
        $this->assertSame('ليبي', $employee->nationality);
        $this->assertSame('ذكر', $employee->gender);
        $this->assertSame('1962-07-01', $employee->birth_date->toDateString());
        $this->assertSame('5797.00', $employee->basic_salary);
        $this->assertSame(8, $employee->children_count);
        $this->assertSame('مصرف الوحدة', $employee->bank_name);
    }

    #[Test]
    public function an_invalid_profile_value_is_rejected(): void
    {
        $employee = Employee::factory()->create();

        $this->from(route('employees.edit', $employee))
            ->put(route('employees.update', $employee), [
                'user_id' => $employee->user_id,
                'location_id' => $employee->location_id,
                'department_id' => $employee->department_id,
                'center_id' => $employee->center_id,
                'birth_date' => 'not-a-date',
                'basic_salary' => 'not-a-number',
            ])
            ->assertSessionHasErrors(['birth_date', 'basic_salary']);
    }

    #[Test]
    public function the_profile_is_optional_so_an_employee_can_be_created_without_it(): void
    {
        $response = $this->post(route('employees.store'), [
            'number' => 991234,
            'english_name' => 'Minimal Person',
            'user_id' => User::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'department_id' => Department::factory()->create()->id,
            'center_id' => Center::factory()->create()->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('employees', ['number' => 991234, 'arabic_name' => null]);
    }
}
