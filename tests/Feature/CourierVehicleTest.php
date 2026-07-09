<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierVehicle;
use App\Modules\Courier\Services\CourierVehiclePresenter;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierVehicleTest extends TestCase
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

    public function test_vehicles_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.vehicles.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_vehicles_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'vehicle_type' => 'motorcycle',
            'plate' => '34 AY 1001',
            'brand' => 'Honda',
            'model' => 'Activa S',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.vehicles.index'));

        $response->assertOk();
        $response->assertSee('Araç Bilgileri');
        $response->assertSee('Kuryelere ait araç bilgilerini yönetin.');
        $response->assertSee('Yeni Araç');
        $response->assertSee('Toplam Araç');
        $response->assertSee('Motosiklet');
        $response->assertSee('Otomobil');
        $response->assertSee('Aktif Araç');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('34 AY 1001');
    }

    public function test_authenticated_user_can_view_vehicle_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        $vehicle = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'vehicle_type' => 'motorcycle',
            'plate' => '34 AY 1001',
            'brand' => 'Honda',
            'model' => 'Activa S',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.vehicles.show', $vehicle->id));

        $response->assertOk();
        $response->assertSee('Araç Detayı');
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Araç Bilgileri');
        $response->assertSee('Ruhsat Bilgileri');
        $response->assertSee('Sigorta Bilgileri');
        $response->assertSee('Araç Geçmişi');
        $response->assertSee('34 AY 1001');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_insurance_status_is_computed_from_expiry_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);
        $presenter = app(CourierVehiclePresenter::class);

        $valid = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'insurance_expiry_date' => now()->addMonths(6)->toDateString(),
        ]);

        $expiring = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'insurance_expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        $expired = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'insurance_expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertEquals('valid', $presenter->showRow($valid)['insurance_status']);
        $this->assertEquals('expiring_soon', $presenter->showRow($expiring)['insurance_status']);
        $this->assertEquals('expired', $presenter->showRow($expired)['insurance_status']);
    }

    public function test_pedestrian_vehicle_hides_license_and_insurance_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $vehicle = CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'vehicle_type' => 'pedestrian',
            'plate' => null,
            'license_number' => null,
            'insurance_policy_number' => null,
            'insurance_expiry_date' => null,
        ]);

        $row = app(CourierVehiclePresenter::class)->showRow($vehicle);

        $this->assertEquals('pedestrian', $row['vehicle_type']);
        $this->assertFalse($row['requires_vehicle_docs']);
        $this->assertNull($row['license_status']);
        $this->assertNull($row['insurance_status']);
    }

    public function test_vehicles_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'plate' => '34 AY 1001',
            'status' => 'active',
        ]);

        CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'plate' => '34 AY 4521',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.vehicles.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee('34 AY 4521');
        $response->assertDontSee('34 AY 1001');
    }

    public function test_vehicles_can_be_filtered_by_vehicle_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'vehicle_type' => 'motorcycle',
            'plate' => '34 AY 1001',
        ]);

        CourierVehicle::factory()->create([
            'courier_id' => $courier->id,
            'vehicle_type' => 'car',
            'plate' => '34 SO 7788',
        ]);

        $response = $this->actingAs($user)->get(route('couriers.vehicles.index', [
            'vehicle_type' => 'car',
        ]));

        $response->assertOk();
        $response->assertSee('34 SO 7788');
        $response->assertDontSee('34 AY 1001');
    }

    public function test_courier_vehicle_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('couriers.vehicles.store'), [
            'courier_id' => $courier->id,
            'vehicle_type' => 'motorcycle',
            'plate' => '34 NT 1234',
            'brand' => 'Honda',
            'model' => 'PCX',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('couriers.vehicles.index', ['courier_id' => $courier->id]));

        $this->assertDatabaseHas('courier_vehicles', [
            'courier_id' => $courier->id,
            'plate' => '34 NT 1234',
            'vehicle_type' => 'motorcycle',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
