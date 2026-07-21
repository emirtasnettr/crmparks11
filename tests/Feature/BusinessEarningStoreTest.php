<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEarningStoreTest extends TestCase
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

    public function test_business_earning_store_requires_permission(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-06-15',
            'pricing_model' => 'per_package',
            'package_count' => 100,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('earning_lines', 0);
    }

    public function test_business_earning_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-06-15',
            'pricing_model' => 'per_package',
            'package_count' => 100,
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
            'extra_income' => 100,
            'extra_expense' => 50,
            'deduction' => 25,
            'description' => 'Test hakediş',
        ]);

        $response->assertRedirect(route('businesses.earnings.index', [
            'business_id' => $business->id,
            'period_month' => 6,
            'period_year' => 2026,
        ]));
        $response->assertSessionHas('success', 'Hakediş başarıyla oluşturuldu.');

        $this->assertDatabaseHas('earning_lines', [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'package_count' => 100,
            'period_month' => 6,
            'period_year' => 2026,
            'description' => 'Test hakediş',
        ]);

        $line = \App\Models\EarningLine::query()->first();
        $this->assertNotNull($line);
        $this->assertSame('2026-06-15', $line->work_date?->toDateString());
        $line->load('status');
        $this->assertSame('approved', $line->status?->code);

        $indexResponse = $this->actingAs($user)->get(route('businesses.earnings.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($business->displayName());
        $indexResponse->assertSee($courier->full_name);

        $courierIndex = $this->actingAs($user)->get(route('couriers.earnings.index', [
            'courier_id' => $courier->id,
        ]));
        $courierIndex->assertOk();
        $courierIndex->assertSee($business->displayName());
        $courierIndex->assertSee($courier->full_name);
    }

    public function test_per_package_earning_requires_package_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-21',
            'pricing_model' => 'per_package',
            'revenue_unit_price' => 45,
            'courier_unit_price' => 38,
        ]);

        $response->assertSessionHasErrors('package_count');
        $this->assertDatabaseCount('earning_lines', 0);
    }

    public function test_hourly_earning_persists_hours_times_rates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-21',
            'pricing_model' => 'hourly',
            'worked_hours' => 8,
            'revenue_unit_price' => 225,
            'courier_unit_price' => 150,
            'extra_income' => 0,
            'extra_expense' => 0,
            'deduction' => 0,
            'description' => 'Saatlik tekli',
        ]);

        $response->assertRedirect();

        $line = \App\Models\EarningLine::query()->first();
        $this->assertNotNull($line);
        $this->assertSame('hourly', $line->pricing_model);
        $this->assertEquals(8.0, (float) $line->worked_hours);
        $this->assertEquals(225.0, (float) $line->revenue_unit_price);
        $this->assertEquals(150.0, (float) $line->courier_unit_price);
        $this->assertEquals(1800.0, (float) $line->revenue_total);
        $this->assertEquals(1200.0, (float) $line->courier_total);
        $this->assertEquals(600.0, (float) $line->profit);
    }

    public function test_hourly_earning_requires_worked_hours(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.earnings.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-21',
            'pricing_model' => 'hourly',
            'revenue_unit_price' => 225,
            'courier_unit_price' => 150,
        ]);

        $response->assertSessionHasErrors('worked_hours');
        $this->assertDatabaseCount('earning_lines', 0);
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
