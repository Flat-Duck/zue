<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    public function test_registration_page_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_submit_is_disabled(): void
    {
        $this->post('/register', [
            'number' => 123456,
            'name' => 'Public User',
            'email' => 'public@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
