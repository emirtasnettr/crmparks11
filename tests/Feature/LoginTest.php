<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AdminUserSeeder::class);
    }

    public function test_login_page_does_not_show_demo_accounts(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee('Demo Hesaplar');
        $response->assertDontSee('mudur@crmlog.com');
        $response->assertDontSee('operasyon@crmlog.com');
    }

    public function test_admin_can_login_with_demo_credentials(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@crmlog.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'admin@crmlog.com')->first());
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@crmlog.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
