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
        $response->assertSee(route('finance.dashboard.index'), false);
        $response->assertSee(route('finance.current-accounts.index'), false);
        $response->assertSee(route('finance.revenues.index'), false);
        $response->assertDontSee('Bu modül şimdilik pasif', false);
        $response->assertSee(route('users.index'), false);
        $response->assertSee(route('roles.index'), false);
        $response->assertSee(route('permissions.index'), false);
        $response->assertSee(route('users.activity-log.index'), false);
        $response->assertSee(route('notifications.index'), false);
        $response->assertSee(route('settings.index'), false);
        $response->assertSee(route('form-builder.index'), false);
        $response->assertSee(route('landing-page-builder.index'), false);
        $response->assertSee(route('stock.products.index'), false);
        $response->assertSee('Stok Yönetimi');
        $response->assertSee('Ayarlar');
        $response->assertSee('Sistem Ayarları');
        $response->assertSee('Kullanıcılar');
        $response->assertSee('Roller');
        $response->assertSee('Yetkiler');
        $response->assertDontSee(route('policy-settings.index'), false);
        $response->assertDontSee('Yakında');
    }

    public function test_general_manager_sees_finance_module_links(): void
    {
        $user = User::factory()->create();
        $user->assignRole('general_manager');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('finance.dashboard.index'), false);
        $response->assertSee(route('finance.collections.index'), false);
        $response->assertDontSee('Bu modül şimdilik pasif', false);
        $response->assertDontSee(route('settings.index'), false);
    }

    public function test_operations_specialist_does_not_see_settings_link(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('href="'.route('businesses.index').'"', false);
        $response->assertDontSee('href="'.route('businesses.contacts.index').'"', false);
        $response->assertDontSee('href="'.route('businesses.contracts.index').'"', false);
        $response->assertDontSee('href="'.route('businesses.documents.index').'"', false);
        $response->assertDontSee('href="'.route('businesses.activities.index').'"', false);
        $response->assertSee('href="'.route('shift-planning.index').'"', false);
        $response->assertSee('İşletmeler');
        $response->assertDontSee('Atanan Kuryeler');
        $response->assertSee('Vardiya Planlama');
        $response->assertSee('Stok Yönetimi');
        $response->assertSee('href="'.route('stock.dashboard').'"', false);
        $response->assertSee('href="'.route('stock.products.index').'"', false);
        $response->assertSee('href="'.route('stock.assignments.index').'"', false);
        $response->assertSee('href="'.route('stock.activity.index').'"', false);
        $response->assertSee('Envanter Durumu');
        $response->assertSee('Kayıt Geçmişi');
        $response->assertDontSee('href="'.route('businesses.earnings.index').'"', false);
        $response->assertDontSee('href="'.route('couriers.earnings.index').'"', false);
        $response->assertDontSee('href="'.route('agencies.earnings.index').'"', false);
        $response->assertDontSee('Hakedişler');
        $response->assertSee('Form Başvuruları');
        $response->assertSee('href="'.route('form-applications.index').'"', false);
        $response->assertDontSee('href="'.route('settings.index').'"', false);
        $response->assertDontSee('Ayarlar');
        $response->assertDontSee('href="'.route('users.index').'"', false);
        $response->assertDontSee('href="'.route('form-builder.index').'"', false);

        $this->actingAs($user)->get(route('businesses.index'))->assertOk();
        $this->actingAs($user)->get(route('businesses.contacts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('businesses.contracts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('businesses.documents.index'))->assertForbidden();
        $this->actingAs($user)->get(route('businesses.activities.index'))->assertForbidden();
        $this->actingAs($user)->get(route('shift-planning.index'))->assertOk();
        $this->actingAs($user)->get(route('stock.products.index'))->assertOk();
        $this->actingAs($user)->get(route('stock.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('stock.assignments.index'))->assertOk();
        $this->actingAs($user)->get(route('stock.activity.index'))->assertOk();
        $this->actingAs($user)->get(route('stock.products.create'))->assertOk();
    }

    public function test_sales_manager_cannot_see_or_access_couriers_and_agencies(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        $dashboard = $this->actingAs($user)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee(route('businesses.index'), false);
        $dashboard->assertSee(route('businesses.contacts.index'), false);
        $dashboard->assertSee(route('businesses.contracts.index'), false);
        $dashboard->assertSee(route('businesses.documents.index'), false);
        $dashboard->assertSee(route('businesses.activities.index'), false);
        $dashboard->assertSee(route('reports.index'), false);
        $dashboard->assertSee(route('form-applications.index'), false);
        $dashboard->assertSee('Sözleşmeler');
        $dashboard->assertSee('Evraklar');
        $dashboard->assertSee('Hareket Geçmişi');
        $dashboard->assertSee('Raporlar');
        $dashboard->assertSee('Form Başvuruları');
        $dashboard->assertDontSee(route('businesses.earnings.index'), false);
        $dashboard->assertDontSee('Hakedişler');
        $dashboard->assertDontSee(route('couriers.index'), false);
        $dashboard->assertDontSee(route('agencies.index'), false);
        $dashboard->assertDontSee(route('form-builder.index'), false);
        $dashboard->assertDontSee(route('landing-page-builder.index'), false);
        $dashboard->assertDontSee(route('settings.index'), false);
        $dashboard->assertDontSee('Ayarlar');

        $this->actingAs($user)->get(route('couriers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('agencies.index'))->assertForbidden();
        $this->actingAs($user)->get(route('form-builder.index'))->assertForbidden();
        $this->actingAs($user)->get(route('landing-page-builder.index'))->assertForbidden();
    }
}
