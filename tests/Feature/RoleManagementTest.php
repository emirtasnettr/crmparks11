<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Models\RoleProfile;
use App\Modules\User\Services\RoleManagementService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_roles_index_requires_authentication(): void
    {
        $response = $this->get(route('roles.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_roles_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('Roller');
        $response->assertSee('Kullanıcı rollerini yönetin.');
        $response->assertSee('Yeni Rol');
        $response->assertSee('Toplam Rol');
        $response->assertSee('Aktif Rol');
        $response->assertSee('Toplam Kullanıcı');
        $response->assertSee('Toplam Yetki');
        $response->assertSee('rol kaydı listeleniyor');
        $response->assertSee('Süper Admin');
        $response->assertSee('Genel Müdür');
        $response->assertSee('Satış Müdürü');
        $response->assertSee('Operasyon Uzmanı');
        $response->assertSee('İşletme');
        $response->assertSee('Kurye');
        $response->assertSee('Acente');
        $response->assertSee('Sistem Rolü');
        $response->assertDontSee('Finans Sorumlusu');
        $response->assertDontSee('Operasyon Yöneticisi');
        $response->assertDontSee('Operasyon Personeli');
        $response->assertDontSee('Raporlama Analisti');
    }

    public function test_user_without_permission_cannot_view_roles_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_default_system_roles_exist(): void
    {
        $roles = app(RoleManagementService::class)->index([])['roles'];

        $this->assertCount(7, $roles);

        $superAdmin = collect($roles)->firstWhere('name', 'super_admin');
        $this->assertNotNull($superAdmin);
        $this->assertFalse($superAdmin['is_deletable']);
        $this->assertFalse($superAdmin['can_deactivate']);
        $this->assertTrue($superAdmin['is_system']);

        $this->assertNotNull(collect($roles)->firstWhere('name', 'operations_specialist'));
    }

    public function test_custom_role_is_deletable(): void
    {
        Role::query()->create([
            'name' => 'ozel_rol',
            'guard_name' => 'web',
        ]);

        RoleProfile::query()->create([
            'role_name' => 'ozel_rol',
            'display_name' => 'Özel Rol',
            'status' => 'active',
            'is_system' => false,
        ]);

        $role = app(RoleManagementService::class)->findByName('ozel_rol');

        $this->assertNotNull($role);
        $this->assertTrue($role['is_deletable']);
        $this->assertFalse($role['is_system']);
    }

    public function test_roles_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        Role::query()->create([
            'name' => 'pasif_ozel_rol',
            'guard_name' => 'web',
        ]);

        RoleProfile::query()->create([
            'role_name' => 'pasif_ozel_rol',
            'display_name' => 'Pasif Özel Rol',
            'status' => 'inactive',
            'is_system' => false,
        ]);

        $response = $this->actingAs($user)->get(route('roles.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('Pasif Özel Rol');
    }

    public function test_authenticated_user_can_view_role_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $response = $this->actingAs($user)->get(route('roles.show', $superAdminRole->id));

        $response->assertOk();
        $response->assertSee('Süper Admin');
        $response->assertSee('super_admin');
        $response->assertSee('Rol Bilgileri');
        $response->assertSee('Atanmış Kullanıcılar');
        $response->assertSee('Atanmış Yetkiler');
        $response->assertSee('Son Güncelleme');
        $response->assertSee('Silinemez');
        $response->assertSee('user.view');
    }

    public function test_role_detail_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('roles.show', 9999));

        $response->assertNotFound();
    }
}
