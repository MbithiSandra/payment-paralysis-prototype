<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CreditDecision;
use App\Models\Invoice;
use App\Models\Sme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrototypeTest extends TestCase
{
    use RefreshDatabase;

    /** An SME owner: a login in users plus a business in smes. Password is "password". */
    private function makeOwner(string $pin = 'A012345678Z'): User
    {
        $user = User::factory()->create(['email' => strtolower($pin) . '@test.co.ke']);
        Sme::forceCreate([
            'kra_pin' => $pin,
            'user_id' => $user->id,
            'business_name' => 'Wanjiru Traders',
            'sector' => 'Retail',
            'phone' => '0712345678',
        ]);
        return $user;
    }

    private function makeClient(string $pin, string $code = 'C001', string $name = 'Kamau Mini Mart'): Client
    {
        return Client::create([
            'client_code' => $code,
            'kra_pin' => $pin,
            'client_name' => $name,
            'sector' => 'Retail',
            'employees' => 5,
            'years_in_operation' => 3,
            'contact_person' => 'Peter Kamau',
            'phone' => '0700000001',
            'email' => 'peter@kamau.co.ke',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_owner_can_log_in(): void
    {
        $user = $this->makeOwner();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeOwner();
        $this->post('/login', ['email' => $user->email, 'password' => 'Wrong123']);
        $this->assertGuest();
    }

    public function test_sql_injection_login_is_rejected(): void
    {
        $this->makeOwner();
        $this->post('/login', ['email' => "' OR '1'='1", 'password' => "' OR '1'='1"]);
        $this->assertGuest();
    }

    public function test_dashboard_loads_for_owner(): void
    {
        $this->actingAs($this->makeOwner())->get('/dashboard')->assertOk();
    }

    public function test_owner_cannot_open_another_sme_client(): void
    {
        $a = $this->makeOwner('A000000001A');
        $this->makeOwner('B000000002B');
        $client = $this->makeClient('B000000002B', 'C900');
        $status = $this->actingAs($a)->get('/clients/' . $client->client_code)->status();
        $this->assertContains($status, [403, 404]);
    }

    public function test_client_contact_details_are_encrypted(): void
    {
        $this->makeOwner();
        $client = $this->makeClient('A012345678Z');
        $raw = DB::table('clients')->where('client_code', 'C001')->value('phone');
        $this->assertNotEquals('0700000001', $raw);
        $this->assertEquals('0700000001', $client->fresh()->phone);
    }

    public function test_password_is_hashed(): void
    {
        $user = $this->makeOwner();
        $raw = DB::table('users')->where('id', $user->id)->value('password');
        $this->assertNotEquals('password', $raw);
        $this->assertTrue(Hash::check('password', $raw));
    }

    public function test_invoice_days_late_is_correct(): void
    {
        $invoice = new Invoice(['issue_date' => '2026-01-01', 'due_date' => '2026-01-10', 'payment_date' => '2026-01-20']);
        $this->assertEquals(10, $invoice->days_late);
        $this->assertEquals('Paid late', $invoice->status);
    }

    public function test_every_tier_has_an_action(): void
    {
        foreach (['Low', 'Medium', 'High'] as $tier) {
            $this->assertNotEmpty(CreditDecision::expectedFor($tier));
        }
    }

    public function test_client_name_script_is_escaped(): void
    {
        $user = $this->makeOwner();
        $this->makeClient('A012345678Z', 'C002', '<script>alert(1)</script>');
        $this->actingAs($user)->get('/clients')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
