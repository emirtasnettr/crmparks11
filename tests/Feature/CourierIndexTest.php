<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_couriers_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_couriers_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.index'));

        $response->assertOk();
        $response->assertSee('Kuryeler');
        $response->assertSee('Sistemde kayıtlı tüm kuryeleri buradan yönetin.');
        $response->assertSee('Yeni Kurye');
        $response->assertSee('Toplam Kurye');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('Esnaf Kurye');
        $response->assertSee('Acente Kuryesi');
    }

    public function test_couriers_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.index', [
            'courier_type' => 'agency',
        ]));

        $response->assertOk();
        $response->assertSee('Emre Demir');
        $response->assertDontSee('Ahmet Yıldız');
    }
}
