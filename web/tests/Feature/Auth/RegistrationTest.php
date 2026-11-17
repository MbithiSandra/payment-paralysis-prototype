<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Wanjiru',
            'email' => 'jane@test.co.ke',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'kra_pin' => 'A012345678Z',
            'business_name' => 'Wanjiru Traders',
            'sector' => 'Retail',
            'phone' => '0712345678',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
