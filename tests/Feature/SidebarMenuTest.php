<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_sidebar_contains_active_module_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('businesses.index'), false);
        $response->assertSee(route('couriers.index'), false);
        $response->assertSee(route('agencies.index'), false);
        $response->assertSee('Finans');
        $response->assertDontSee(route('finance.dashboard.index'), false);
        $response->assertSee('aria-disabled="true"', false);
        $response->assertSee('Bu modül şimdilik pasif', false);
        $response->assertSee(route('users.index'), false);
        $response->assertSee(route('settings.index'), false);
        $response->assertSee(route('form-builder.index'), false);
        $response->assertSee(route('landing-page-builder.index'), false);
        $response->assertDontSee(route('policy-settings.index'), false);
        $response->assertDontSee('Yakında');
    }

    public function test_operations_manager_does_not_see_settings_link(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('businesses.index'), false);
        $response->assertDontSee(route('settings.index'), false);
        $response->assertDontSee(route('users.index'), false);
    }
}
