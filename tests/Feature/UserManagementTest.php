<?php

namespace Tests\Feature;

use App\Core\Enums\UserType;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\User\Services\UserManagementService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_users_index_requires_authentication(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_users_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        User::factory()->withRole('general_manager')->create(['name' => 'Genel Müdür Kullanıcı']);
        User::factory()->withRole('operations_manager')->create(['name' => 'Operasyon Yöneticisi Kullanıcı']);
        User::factory()->withRole('courier')->create(['name' => 'Kurye Kullanıcı', 'user_type' => UserType::Courier]);

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

    public function test_users_are_loaded_from_database(): void
    {
        User::factory()->count(5)->create();

        $users = app(UserManagementService::class)->index([])['users'];

        $this->assertGreaterThanOrEqual(5, count($users));
    }

    public function test_users_can_be_filtered_by_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        User::factory()->withRole('courier')->create([
            'name' => 'Ahmet Kurye',
            'user_type' => UserType::Courier,
        ]);

        $response = $this->actingAs($user)->get(route('users.index', ['role' => 'courier']));

        $response->assertOk();
        $response->assertSee('Kurye');
        $response->assertSee('Ahmet Kurye');
    }

    public function test_users_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        User::factory()->suspended()->create(['name' => 'Askıdaki Kullanıcı']);

        $response = $this->actingAs($user)->get(route('users.index', ['status' => 'suspended']));

        $response->assertOk();
        $response->assertSee('Askıda');
        $response->assertSee('Askıdaki Kullanıcı');
    }

    public function test_authenticated_user_can_view_user_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $managedUser = User::factory()->withRole('general_manager')->create([
            'name' => 'Elif Demir',
            'last_login_at' => now(),
            'last_login_ip' => '192.168.1.10',
        ]);

        $response = $this->actingAs($user)->get(route('users.show', $managedUser->id));

        $response->assertOk();
        $response->assertSee('Elif Demir');
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Rol Bilgileri');
        $response->assertSee('Son Girişler');
        $response->assertSee('Yetkiler');
        $response->assertSee('Oturum Geçmişi');
        $response->assertSee('İşlem Geçmişi');
        $response->assertSee('dashboard.view');
    }

    public function test_user_profile_shows_linked_business_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $business = Business::factory()->create(['company_name' => 'Burger House Gıda Ltd. Şti.']);
        $managedUser = User::factory()->withRole('business')->create([
            'name' => 'İşletme Yetkilisi',
            'user_type' => UserType::Business,
            'profileable_type' => Business::class,
            'profileable_id' => $business->id,
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $managedUser->id));

        $response->assertOk();
        $response->assertSee('Burger House Gıda Ltd. Şti.');
    }

    public function test_user_profile_returns_404_for_invalid_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('users.show', 9999));

        $response->assertNotFound();
    }
}
