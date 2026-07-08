<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RowActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_business_index_row_actions_include_working_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.index'));

        $response->assertOk();
        $response->assertSee('business-detail', false);
        $response->assertSee('businessListPage', false);
        $response->assertSee(route('businesses.contacts.index', ['business_id' => 1]), false);
    }

    public function test_finance_revenue_index_supports_row_action_modal_dispatch(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.revenues.index'));

        $response->assertOk();
        $response->assertSee('finance-row-action', false);
        $response->assertSee('handleRowAction', false);
    }
}
