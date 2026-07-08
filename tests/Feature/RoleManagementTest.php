<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Data\RoleManagementDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Operasyon Yöneticisi');
        $response->assertSee('Finans Sorumlusu');
        $response->assertSee('Operasyon Personeli');
        $response->assertSee('İşletme');
        $response->assertSee('Kurye');
        $response->assertSee('Acente');
        $response->assertSee('Sistem Rolü');
    }

    public function test_user_without_permission_cannot_view_roles_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_default_system_roles_exist(): void
    {
        $roles = RoleManagementDummyData::all();

        $this->assertGreaterThanOrEqual(8, count($roles));

        $superAdmin = collect($roles)->firstWhere('name', 'super_admin');
        $this->assertNotNull($superAdmin);
        $this->assertFalse($superAdmin['is_deletable']);
        $this->assertFalse($superAdmin['can_deactivate']);
        $this->assertTrue($superAdmin['is_system']);
    }

    public function test_custom_role_is_deletable(): void
    {
        $role = RoleManagementDummyData::find(9);

        $this->assertNotNull($role);
        $this->assertTrue($role['is_deletable']);
        $this->assertFalse($role['is_system']);
    }

    public function test_roles_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('roles.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('Raporlama Analisti');
    }

    public function test_authenticated_user_can_view_role_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('roles.show', 1));

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
