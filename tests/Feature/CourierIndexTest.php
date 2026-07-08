<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            CitySeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
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
        $this->createCourier($user, [
            'first_name' => 'Ahmet',
            'last_name' => 'Yıldız',
            'courier_type' => 'independent',
        ]);
        $this->createCourier($user, [
            'first_name' => 'Emre',
            'last_name' => 'Demir',
            'courier_type' => 'agency',
        ]);

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
        $agency = Agency::factory()->create(['created_by' => $user->id]);
        $this->createCourier($user, [
            'first_name' => 'Emre',
            'last_name' => 'Demir',
            'courier_type' => 'agency',
            'agency_id' => $agency->id,
        ]);
        $this->createCourier($user, [
            'first_name' => 'Ahmet',
            'last_name' => 'Yıldız',
            'courier_type' => 'independent',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.index', [
            'courier_type' => 'agency',
        ]));

        $response->assertOk();
        $response->assertSee('Emre Demir');
        $response->assertDontSee('Ahmet Yıldız');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        $vehicleTypeId = VehicleType::query()->where('code', 'motor')->value('id');

        return Courier::factory()->create(array_merge([
            'vehicle_type_id' => $vehicleTypeId,
            'created_by' => $user->id,
        ], $overrides));
    }
}
