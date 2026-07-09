<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyEarningTest extends TestCase
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

    public function test_agency_earnings_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.earnings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_earnings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, ['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['agency_id' => $agency->id, 'courier_type' => 'agency']);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'agency_payment' => 1500,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.earnings.index'));

        $response->assertOk();
        $response->assertSee('Hakedişler');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
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

    private function createBusiness(User $user): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Business::factory()->create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ]);
    }

    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
