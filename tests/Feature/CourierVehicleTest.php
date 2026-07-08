<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Courier\Data\CourierVehicleDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierVehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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
    }

    public function test_authenticated_user_can_view_vehicle_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('couriers.vehicles.show', 1));

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
        $valid = CourierVehicleDummyData::find(1);
        $expiring = CourierVehicleDummyData::find(3);
        $expired = CourierVehicleDummyData::find(6);

        $this->assertEquals('valid', $valid['insurance_status']);
        $this->assertEquals('expiring_soon', $expiring['insurance_status']);
        $this->assertEquals('expired', $expired['insurance_status']);
    }

    public function test_pedestrian_vehicle_hides_license_and_insurance_fields(): void
    {
        $vehicle = CourierVehicleDummyData::find(23);

        $this->assertNotNull($vehicle);
        $this->assertEquals('pedestrian', $vehicle['vehicle_type']);
        $this->assertFalse($vehicle['requires_vehicle_docs']);
        $this->assertNull($vehicle['license_status']);
        $this->assertNull($vehicle['insurance_status']);
    }

    public function test_all_vehicle_records_are_preserved(): void
    {
        $vehicles = CourierVehicleDummyData::all();

        $this->assertCount(35, $vehicles);
        $this->assertGreaterThanOrEqual(30, count($vehicles));
    }

    public function test_vehicles_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

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

        $response = $this->actingAs($user)->get(route('couriers.vehicles.index', [
            'vehicle_type' => 'car',
        ]));

        $response->assertOk();
        $response->assertSee('34 SO 7788');
        $response->assertDontSee('34 AY 1001');
    }
}
