<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\User\Models\RoleProfile;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_create_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'display_name' => 'Test Koordinatörü',
            'description' => 'Test rol açıklaması',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $role = Role::query()->where('name', 'test_koordinatoru')->first();
        $this->assertNotNull($role);

        $this->assertDatabaseHas('role_profiles', [
            'role_name' => 'test_koordinatoru',
            'display_name' => 'Test Koordinatörü',
            'is_system' => false,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'role_created',
            'subject_type' => Role::class,
            'subject_id' => $role->id,
        ]);
    }

    public function test_super_admin_can_update_custom_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $role = Role::query()->create([
            'name' => 'bolge_koordinatoru',
            'guard_name' => 'web',
        ]);

        RoleProfile::query()->create([
            'role_name' => 'bolge_koordinatoru',
            'display_name' => 'Bölge Koordinatörü',
            'description' => 'Özel rol',
            'status' => 'active',
            'is_system' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('roles.update', $role->id), [
            'display_name' => 'Bölge Koordinatörü Güncel',
            'description' => 'Güncellenmiş açıklama',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('roles.show', $role->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('role_profiles', [
            'role_name' => 'bolge_koordinatoru',
            'display_name' => 'Bölge Koordinatörü Güncel',
            'description' => 'Güncellenmiş açıklama',
        ]);
    }

    public function test_system_role_display_name_cannot_be_changed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $role = Role::query()->where('name', 'super_admin')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('roles.update', $role->id), [
            'display_name' => 'Farklı İsim',
            'description' => 'Yeni açıklama',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('roles.show', $role->id));

        $profile = RoleProfile::query()->where('role_name', 'super_admin')->first();
        $this->assertNotNull($profile);
        $this->assertSame('Süper Admin', $profile->display_name);
        $this->assertSame('Yeni açıklama', $profile->description);
    }

    public function test_super_admin_can_delete_unused_custom_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $role = Role::query()->create([
            'name' => 'gecici_rol',
            'guard_name' => 'web',
        ]);

        RoleProfile::query()->create([
            'role_name' => 'gecici_rol',
            'display_name' => 'Geçici Rol',
            'status' => 'active',
            'is_system' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role->id));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('role_profiles', ['role_name' => 'gecici_rol']);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $role = Role::query()->where('name', 'super_admin')->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role->id));

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_user_without_permission_cannot_create_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $response = $this->actingAs($user)->post(route('roles.store'), [
            'display_name' => 'Yetkisiz Rol',
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }
}
