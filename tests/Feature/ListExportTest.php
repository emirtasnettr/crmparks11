<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_business_export_requires_authentication(): void
    {
        $response = $this->get(route('businesses.export'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_export_business_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.export'));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_business_export_respects_status_filter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.export', ['status' => 'active']));

        $response->assertOk();
        $response->assertDownload();
    }

    public function test_finance_profitability_export_downloads_multi_sheet_workbook(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('finance.profitability.export'));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('karlilik-analizi', (string) $response->headers->get('content-disposition'));
    }

    public function test_list_pages_render_active_export_button(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.index'));

        $response->assertOk();
        $response->assertSee(route('businesses.export', [], false), false);
        $response->assertSee('Excel&#039;e Aktar', false);
    }
}
