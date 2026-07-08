<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_contracts_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.contracts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_contracts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.contracts.index'));

        $response->assertOk();
        $response->assertSee('Sözleşmeler');
        $response->assertSee('SZL-2026-001');
        $response->assertSee('Yeni Sözleşme');
        $response->assertSee('Aktif Sözleşme');
    }

    public function test_authenticated_user_can_view_contract_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.contracts.show', 1));

        $response->assertOk();
        $response->assertSee('Sözleşme Bilgileri');
        $response->assertSee('PDF Önizleme');
        $response->assertSee('Burger House');
    }
}
