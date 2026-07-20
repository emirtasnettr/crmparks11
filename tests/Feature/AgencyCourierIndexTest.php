<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyCourierIndexTest extends TestCase
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

    public function test_agency_couriers_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.couriers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_couriers_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);
        $courier = $this->createAgencyCourier($user, $agency, [
            'full_name' => 'Emre Demir',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.couriers.index'));

        $response->assertOk();
        $response->assertSee('Acenteye Bağlı Kuryeler');
        $response->assertSee('Kurye Ata');
        $response->assertSee('Toplam Kurye');
        $response->assertSee('Emre Demir');
        $response->assertSee('Motor');
    }

    public function test_agency_couriers_can_be_filtered_by_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agencyA = $this->createAgency($user);
        $agencyB = $this->createAgency($user, ['company_name' => 'Diğer Acente Ltd. Şti.']);

        $this->createAgencyCourier($user, $agencyA, ['full_name' => 'Emre Demir']);
        $this->createAgencyCourier($user, $agencyA, ['full_name' => 'Burak Şen']);
        $this->createAgencyCourier($user, $agencyB, ['full_name' => 'Ayşe Korkmaz']);

        $response = $this->actingAs($user)->get(route('agencies.couriers.index', [
            'agency_id' => $agencyA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Emre Demir');
        $response->assertSee('Burak Şen');
        $response->assertDontSee('Ayşe Korkmaz');
    }

    public function test_agency_couriers_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        $this->createAgencyCourier($user, $agency, [
            'full_name' => 'Oğuz Yılmaz',
            'status' => 'on_leave',
        ]);
        $this->createAgencyCourier($user, $agency, [
            'full_name' => 'Caner Bilgin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.couriers.index', [
            'status' => 'on_leave',
        ]));

        $response->assertOk();
        $response->assertSee('Oğuz Yılmaz');
        $response->assertDontSee('Caner Bilgin');
    }

    private function createAgency(User $user, array $overrides = []): Agency
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Agency::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }


    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgencyCourier(User $user, Agency $agency, array $overrides = []): Courier
    {
        $vehicleTypeId = VehicleType::query()->where('code', 'motor')->value('id');

        return Courier::factory()->create(array_merge([
            'agency_id' => $agency->id,
            'courier_type' => 'agency',
            'vehicle_type_id' => $vehicleTypeId,
            'start_date' => now()->subMonths(3)->toDateString(),
            'created_by' => $user->id,
        ], $overrides));
    }
}
