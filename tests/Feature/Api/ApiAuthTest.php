<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
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

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('version', '1.0.0');
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'password123',
        ]);
        $user->assignRole('super_admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password123',
            'device_name' => 'test-suite',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'api@example.com')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'api@example.com']);
        $user->assignRole('super_admin');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_view_me_and_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/businesses')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
