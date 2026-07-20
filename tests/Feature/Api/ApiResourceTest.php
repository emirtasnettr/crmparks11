<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_super_admin_can_list_and_show_businesses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['company_name' => 'API Test İşletme']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/businesses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'page', 'per_page', 'last_page']]);

        $this->getJson('/api/v1/businesses/'.$business->id)
            ->assertOk()
            ->assertJsonPath('data.company_name', 'API Test İşletme');
    }

    public function test_super_admin_can_list_couriers_and_agencies(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = Courier::factory()->create(['full_name' => 'API Kurye']);
        $agency = Agency::factory()->create(['company_name' => 'API Acente']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/couriers')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/couriers/'.$courier->id)
            ->assertOk()
            ->assertJsonPath('data.full_name', 'API Kurye');

        $this->getJson('/api/v1/agencies')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/agencies/'.$agency->id)
            ->assertOk()
            ->assertJsonPath('data.company_name', 'API Acente');
    }

    public function test_dashboard_and_earnings_endpoints_work(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'stats',
                'latest_businesses',
                'latest_couriers',
                'courier_type_distribution',
                'finance',
                'pending_collections',
                'pending_payments',
                'pending_earnings',
            ]]);

        $this->getJson('/api/v1/earnings')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/notifications/unread')
            ->assertOk()
            ->assertJsonStructure(['data' => ['unread_count', 'items']]);
    }

    public function test_user_without_permission_cannot_list_businesses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('courier');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/businesses')->assertForbidden();
    }

    public function test_super_admin_can_create_and_update_business_via_api(): void
    {
        $this->seed(\Database\Seeders\CitySeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/businesses', [
            'company_name' => 'API Yazma İşletme Ltd. Şti.',
            'brand_name' => 'API Marka',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 3,
            'status' => 'active',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_name', 'API Yazma İşletme Ltd. Şti.');

        $id = $create->json('data.id');

        $this->putJson('/api/v1/businesses/'.$id, [
            'company_name' => 'API Güncel İşletme Ltd. Şti.',
            'brand_name' => 'API Marka',
            'phone' => '0212 111 22 33',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 3,
            'status' => 'active',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
        ])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'API Güncel İşletme Ltd. Şti.');
    }

    public function test_super_admin_can_create_and_approve_earning_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = Business::factory()->create(['created_by' => $user->id]);
        $courier = Courier::factory()->create(['created_by' => $user->id]);

        app(\App\Modules\Setting\Services\SettingsManager::class)->group('earnings')->save([
            'default_period' => 'monthly',
            'approval_process' => 'single',
        ]);

        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/earnings', [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'period_month' => 7,
            'period_year' => 2026,
            'pricing_model' => 'per_package',
            'package_count' => 50,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
            'status' => 'pending',
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->postJson('/api/v1/earnings/'.$id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }
}
