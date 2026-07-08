<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Data\PermissionManagementDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_permissions_index_requires_authentication(): void
    {
        $response = $this->get(route('permissions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_permissions_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('permissions.index'));

        $response->assertOk();
        $response->assertSee('Yetkiler');
        $response->assertSee('Sistem yetkilerini rol bazlı yönetin.');
        $response->assertSee('Rol Seç');
        $response->assertSee('Kaydet');
        $response->assertSee('Tümünü Seç');
        $response->assertSee('Tümünü Kaldır');
        $response->assertSee('Varsayılana Döndür');
        $response->assertSee('Toplam Rol');
        $response->assertSee('Toplam Yetki');
        $response->assertSee('Aktif Yetki');
        $response->assertSee('Pasif Yetki');
        $response->assertSee('Modül Ara');
        $response->assertSee('Yetki Ara');
        $response->assertSee('Modül');
        $response->assertSee('Görüntüle');
        $response->assertSee('Oluştur');
        $response->assertSee('Güncelle');
        $response->assertSee('Sil');
        $response->assertSee('Dışa Aktar');
        $response->assertSee('Yazdır');
        $response->assertSee('Onayla');
        $response->assertSee('Tüm Yetkileri Seç');
        $response->assertSee('İşletmeler');
        $response->assertSee('Finans Dashboard');
        $response->assertSee('Sistem Ayarları');
        $response->assertSee('permissionManagementPage');
    }

    public function test_user_without_permission_cannot_view_permissions_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('permissions.index'));

        $response->assertForbidden();
    }

    public function test_permissions_page_supports_role_query_parameter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('permissions.index', ['role' => 'finance_officer']));

        $response->assertOk();
        $response->assertSee('Finans Sorumlusu', false);
        $response->assertSee('finance_officer', false);
    }

    public function test_super_admin_role_is_locked_in_payload(): void
    {
        $payload = PermissionManagementDummyData::rolesPayload();

        $this->assertTrue($payload['super_admin']['is_locked']);
        $this->assertFalse($payload['general_manager']['is_locked']);
    }

    public function test_selectable_roles_include_defaults(): void
    {
        $roles = PermissionManagementDummyData::selectableRoles();

        $this->assertArrayHasKey('super_admin', $roles);
        $this->assertArrayHasKey('general_manager', $roles);
        $this->assertArrayHasKey('finance_officer', $roles);
        $this->assertArrayHasKey('courier', $roles);
        $this->assertCount(8, $roles);
    }

    public function test_finance_officer_has_finance_module_permissions(): void
    {
        $payload = PermissionManagementDummyData::rolesPayload();
        $matrix = collect($payload['finance_officer']['matrix']);

        $revenues = $matrix->firstWhere('key', 'revenues');
        $this->assertNotNull($revenues);
        $this->assertTrue($revenues['actions']['view']['granted']);
        $this->assertTrue($revenues['actions']['create']['granted']);

        $users = $matrix->firstWhere('key', 'users');
        $this->assertNotNull($users);
        $this->assertFalse($users['actions']['view']['granted']);
    }

    public function test_courier_role_has_limited_view_own_permissions(): void
    {
        $payload = PermissionManagementDummyData::rolesPayload();
        $matrix = collect($payload['courier']['matrix']);

        $couriers = $matrix->firstWhere('key', 'couriers');
        $this->assertTrue($couriers['actions']['view']['granted']);

        $businesses = $matrix->firstWhere('key', 'businesses');
        $this->assertFalse($businesses['actions']['view']['granted']);
    }

    public function test_audit_log_payload_structure_is_ready(): void
    {
        $payload = PermissionManagementDummyData::auditLogPayload(
            'general_manager',
            ['dashboard.view'],
            ['dashboard.view', 'report.export']
        );

        $this->assertSame('permission_changes', $payload['log_name']);
        $this->assertContains('report.export', $payload['properties']['added']);
    }
}
