<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Data\UserManagementDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_users_index_requires_authentication(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_users_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Kullanıcılar');
        $response->assertSee('Sistemdeki tüm kullanıcı hesaplarını yönetin.');
        $response->assertSee('Yeni Kullanıcı');
        $response->assertSee('Excel');
        $response->assertSee('Aktar');
        $response->assertSee('Toplam Kullanıcı');
        $response->assertSee('Aktif Kullanıcı');
        $response->assertSee('Pasif Kullanıcı');
        $response->assertSee('Bugün Giriş Yapan');
        $response->assertSee('kullanıcı kaydı listeleniyor');
        $response->assertSee('Süper Admin');
        $response->assertSee('Genel Müdür');
        $response->assertSee('Operasyon Yöneticisi');
        $response->assertSee('Finans Sorumlusu');
    }

    public function test_user_without_permission_cannot_view_users_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_dummy_data_has_at_least_forty_users(): void
    {
        $this->assertGreaterThanOrEqual(40, count(UserManagementDummyData::all()));
    }

    public function test_users_can_be_filtered_by_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('users.index', ['role' => 'courier']));

        $response->assertOk();
        $response->assertSee('Kurye');
    }

    public function test_users_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('users.index', ['status' => 'suspended']));

        $response->assertOk();
        $response->assertSee('Askıda');
    }

    public function test_authenticated_user_can_view_user_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $managedUser = UserManagementDummyData::find(1);

        $response = $this->actingAs($user)->get(route('users.show', 1));

        $response->assertOk();
        $response->assertSee($managedUser['full_name']);
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Rol Bilgileri');
        $response->assertSee('Son Girişler');
        $response->assertSee('Yetkiler');
        $response->assertSee('Oturum Geçmişi');
        $response->assertSee('İşlem Geçmişi');
        $response->assertSee('user.view');
    }

    public function test_user_profile_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('users.show', 9999));

        $response->assertNotFound();
    }
}
