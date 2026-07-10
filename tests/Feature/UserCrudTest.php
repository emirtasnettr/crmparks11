<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
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

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Yeni',
            'last_name' => 'Kullanıcı',
            'email' => 'yeni.kullanici@example.com',
            'phone' => '05551234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['operations_manager'],
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'yeni.kullanici@example.com',
            'name' => 'Yeni Kullanıcı',
            'phone' => '05551234567',
        ]);

        $created = User::query()->where('email', 'yeni.kullanici@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('operations_manager'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_created',
            'subject_type' => User::class,
            'subject_id' => $created->id,
        ]);
    }

    public function test_super_admin_can_update_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $managed = User::factory()->withRole('operations_manager')->create([
            'name' => 'Eski Ad',
            'email' => 'eski@example.com',
            'phone' => '05559876543',
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $managed->id), [
            'first_name' => 'Güncel',
            'last_name' => 'Ad',
            'email' => 'guncel@example.com',
            'phone' => '05551112233',
            'roles' => ['general_manager'],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.show', $managed->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $managed->id,
            'name' => 'Güncel Ad',
            'email' => 'guncel@example.com',
        ]);

        $managed->refresh();
        $this->assertTrue($managed->hasRole('general_manager'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_updated',
            'subject_type' => User::class,
            'subject_id' => $managed->id,
        ]);
    }

    public function test_super_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $managed = User::factory()->withRole('operations_manager')->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $managed->id));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $managed->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_deleted',
            'subject_type' => User::class,
            'subject_id' => $managed->id,
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin->id));

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_user_without_permission_cannot_create_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_manager');

        $response = $this->actingAs($user)->post(route('users.store'), [
            'first_name' => 'Yetkisiz',
            'last_name' => 'Kullanıcı',
            'email' => 'yetkisiz@example.com',
            'phone' => '05554443322',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['operations_manager'],
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }
}
