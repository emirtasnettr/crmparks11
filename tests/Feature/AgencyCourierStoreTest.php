<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyCourierStoreTest extends TestCase
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

    public function test_agency_courier_store_requires_permission(): void
    {
        $user = User::factory()->create();
        $agency = $this->createAgency($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('agencies.couriers.store'), [
            'agency_id' => $agency->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $courier->refresh();
        $this->assertNull($courier->agency_id);
    }

    public function test_agency_courier_can_be_assigned_from_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, [
            'company_name' => 'Hızlı Kurye Lojistik Ltd. Şti.',
        ]);
        $courier = $this->createCourier($user, [
            'full_name' => 'Emre Demir',
            'phone' => '0534 100 10 03',
            'courier_type' => 'independent',
            'agency_id' => null,
        ]);

        $response = $this->actingAs($user)->post(route('agencies.couriers.store'), [
            'agency_id' => $agency->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'notes' => 'Test ataması',
            'status' => 'active',
        ]);

        $courier->refresh();

        $response->assertRedirect(route('agencies.couriers.index', ['agency_id' => $agency->id]));
        $response->assertSessionHas('success', 'Kurye acenteye başarıyla atandı.');

        $this->assertSame($agency->id, $courier->agency_id);
        $this->assertSame('agency', $courier->courier_type);
        $this->assertSame('2026-01-01', $courier->start_date?->toDateString());

        $log = ActivityLog::query()->where('action', 'courier_assigned')->first();
        $this->assertNotNull($log);
        $this->assertSame($courier->id, $log->subject_id);

        $indexResponse = $this->actingAs($user)->get(route('agencies.couriers.index', ['agency_id' => $agency->id]));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Emre Demir');
        $indexResponse->assertSee($agency->displayName());
    }

    public function test_agency_courier_can_be_assigned_from_agency_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Burak Şen',
            'agency_id' => null,
        ]);

        $response = $this->actingAs($user)->post(route('agencies.couriers.store'), [
            'agency_id' => $agency->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-02-01',
            'redirect_to_agency' => true,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('agencies.show', $agency->id).'?tab=couriers');

        $showResponse = $this->actingAs($user)->get(route('agencies.show', $agency->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Burak Şen');
    }

    public function test_already_assigned_courier_cannot_be_reassigned(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agencyA = $this->createAgency($user);
        $agencyB = $this->createAgency($user, ['company_name' => 'Metro Lojistik A.Ş.']);
        $courier = $this->createCourier($user, [
            'agency_id' => $agencyA->id,
            'courier_type' => 'agency',
        ]);

        $response = $this->actingAs($user)->post(route('agencies.couriers.store'), [
            'agency_id' => $agencyB->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-03-01',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('courier_id');
        $courier->refresh();
        $this->assertSame($agencyA->id, $courier->agency_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
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
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
