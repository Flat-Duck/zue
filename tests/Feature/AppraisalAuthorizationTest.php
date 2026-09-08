<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_manage_appraisal_configuration(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('appraisals.items.index'))->assertForbidden();
        $this->get(route('appraisals.forms.index'))->assertForbidden();
        $this->get(route('appraisals.periods.index'))->assertForbidden();
        $this->get(route('appraisals.official.index'))->assertForbidden();
    }
}
