<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEarningTest extends TestCase
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

    public function test_earnings_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.earnings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_earnings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'period_month' => 6,
            'period_year' => 2026,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.earnings.index'));

        $response->assertOk();
        $response->assertSee('Hakedişler');
        $response->assertSee('Tekli Hakediş');
        $response->assertSee($business->displayName());
        $response->assertSee($courier->full_name);
    }

    public function test_authenticated_user_can_view_earning_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $line = EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'period_month' => 6,
            'period_year' => 2026,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.earnings.show', $line->id));

        $response->assertOk();
        $response->assertSee('Hakediş Detayı');
        $response->assertSee($business->displayName());
        $response->assertSee($courier->full_name);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBusiness(User $user, array $overrides = []): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Business::factory()->create(array_merge([
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
