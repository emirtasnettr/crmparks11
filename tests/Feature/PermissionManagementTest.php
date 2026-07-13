<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\User\Services\PermissionManagementService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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
        $payload = app(PermissionManagementService::class)->rolesPayload();

        $this->assertTrue($payload['super_admin']['is_locked']);
        $this->assertFalse($payload['general_manager']['is_locked']);
    }

    public function test_selectable_roles_include_defaults(): void
    {
        $roles = app(PermissionManagementService::class)->selectableRoles();

        $this->assertArrayHasKey('super_admin', $roles);
        $this->assertArrayHasKey('general_manager', $roles);
        $this->assertArrayHasKey('sales_manager', $roles);
        $this->assertArrayHasKey('finance_officer', $roles);
        $this->assertArrayHasKey('courier', $roles);
        $this->assertCount(9, $roles);
    }

    public function test_sales_manager_has_business_and_report_permissions(): void
    {
        $payload = app(PermissionManagementService::class)->rolesPayload();
        $matrix = collect($payload['sales_manager']['matrix']);

        $businesses = $matrix->firstWhere('key', 'businesses');
        $this->assertNotNull($businesses);
        $this->assertTrue($businesses['actions']['view']['granted']);
        $this->assertTrue($businesses['actions']['create']['granted']);

        $reports = $matrix->firstWhere('key', 'reports');
        $this->assertNotNull($reports);
        $this->assertTrue($reports['actions']['view']['granted']);

        $users = $matrix->firstWhere('key', 'users');
        $this->assertNotNull($users);
        $this->assertFalse($users['actions']['view']['granted']);
    }

    public function test_finance_officer_has_finance_module_permissions(): void
    {
        $payload = app(PermissionManagementService::class)->rolesPayload();
        $matrix = collect($payload['finance_officer']['matrix']);

        $financeDashboard = $matrix->firstWhere('key', 'finance_dashboard');
        $this->assertNotNull($financeDashboard);
        $this->assertTrue($financeDashboard['actions']['view']['granted']);

        $reports = $matrix->firstWhere('key', 'reports');
        $this->assertNotNull($reports);
        $this->assertTrue($reports['actions']['view']['granted']);
        $this->assertTrue($reports['actions']['export']['granted']);

        $users = $matrix->firstWhere('key', 'users');
        $this->assertNotNull($users);
        $this->assertFalse($users['actions']['view']['granted']);
    }

    public function test_courier_role_has_limited_view_own_permissions(): void
    {
        $payload = app(PermissionManagementService::class)->rolesPayload();
        $matrix = collect($payload['courier']['matrix']);

        $couriers = $matrix->firstWhere('key', 'couriers');
        $this->assertTrue($couriers['actions']['view']['granted']);

        $businesses = $matrix->firstWhere('key', 'businesses');
        $this->assertFalse($businesses['actions']['view']['granted']);
    }

    public function test_roles_payload_reflects_database_permissions(): void
    {
        $payload = app(PermissionManagementService::class)->rolesPayload();

        $this->assertTrue(
            collect($payload['super_admin']['matrix'])
                ->firstWhere('key', 'users')['actions']['view']['granted']
        );
        $this->assertContains('user.view', $payload['super_admin']['defaults']);
    }

    public function test_audit_log_payload_structure_is_ready(): void
    {
        $payload = app(PermissionManagementService::class)->auditLogPayload(
            'general_manager',
            ['dashboard.view'],
            ['dashboard.view', 'report.export']
        );

        $this->assertSame('permission_changes', $payload['log_name']);
        $this->assertContains('report.export', $payload['properties']['added']);
    }

    public function test_permission_matrix_update_requires_user_update_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->putJson(route('permissions.update'), [
            'role' => 'finance_officer',
            'permissions' => ['dashboard.view'],
        ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_update_role_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->putJson(route('permissions.update'), [
            'role' => 'finance_officer',
            'permissions' => [
                'dashboard.view',
                'dashboard.financial',
                'earning.view',
                'earning.approve',
                'report.view',
                'report.export',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('role', 'finance_officer');
        $response->assertJsonPath('message', 'Yetki değişiklikleri kaydedildi.');

        $role = Role::findByName('finance_officer');
        $this->assertTrue($role->hasPermissionTo('report.export'));
        $this->assertTrue($role->hasPermissionTo('earning.approve'));
    }

    public function test_sync_role_permissions_rejects_super_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(PermissionManagementService::class)->syncRolePermissions(
            'super_admin',
            ['dashboard.view'],
            $user,
        );
    }

    public function test_permission_matrix_update_writes_activity_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->putJson(route('permissions.update'), [
            'role' => 'operations_staff',
            'permissions' => [
                'dashboard.view',
                'business.view',
                'courier.view',
                'agency.view',
                'assignment.view',
            ],
        ])->assertOk();

        $log = ActivityLog::query()
            ->where('action', 'permission_updated')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringContainsString('Operasyon Personeli', $log->description ?? '');
        $this->assertSame('operations_staff', $log->new_values['role'] ?? null);
    }
}
