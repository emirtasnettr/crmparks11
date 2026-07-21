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

class CourierEarningTest extends TestCase
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

    public function test_courier_earnings_index_requires_authentication(): void
    {
        $response = $this->get(route('couriers.earnings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_courier_earnings_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.earnings.index'));

        $response->assertOk();
        $response->assertSee('Hakedişler');
        $response->assertSee('Ahmet Yıldız');
    }

    public function test_courier_earnings_index_filters_by_courier_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courierA = $this->createCourier($user, ['full_name' => 'Filtre Kurye A']);
        $courierB = $this->createCourier($user, ['full_name' => 'Filtre Kurye B']);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courierA->id,
            'created_by' => $user->id,
        ]);
        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courierB->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.earnings.index', [
            'courier_id' => $courierA->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('1 kayıt listeleniyor');
        $response->assertSee('Filtre Kurye A');
        $response->assertSee('value="'.$courierA->id.'"', false);
        $response->assertSee('value="'.$courierB->id.'"', false);
        $response->assertDontSee('value="0"', false);
    }

    public function test_filter_select_options_preserves_numeric_keys(): void
    {
        $options = filter_select_options([357 => 'Ali', 358 => 'Veli']);

        $this->assertSame(['all' => 'Tümü', 357 => 'Ali', 358 => 'Veli'], $options);
    }

    public function test_courier_earnings_index_shows_worked_hours_for_hourly_lines(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Saatlik Kurye']);

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'pricing_model' => 'hourly',
            'earning_type' => 'hourly',
            'package_count' => 0,
            'worked_hours' => 18.5,
            'courier_unit_price' => 220,
            'courier_total' => 4070,
            'net_courier_payment' => 4070,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.earnings.index'));

        $response->assertOk();
        $response->assertSeeText('Saat');
        $response->assertSeeText('18,50 sa');
    }

    public function test_authenticated_user_can_view_courier_earning_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Detay Kurye']);

        $line = EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-15',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('couriers.earnings.show', $line->id));

        $response->assertOk();
        $response->assertSee('Hakediş Detayı');
        $response->assertSee('Detay Kurye');
        $response->assertSee('15.07.2026');
        $response->assertSee('Tutar Düzeltmeleri');
    }

    public function test_courier_earning_show_works_when_financial_adjustments_table_missing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Migrasyon Kurye']);

        $line = EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
        ]);

        \Illuminate\Support\Facades\Schema::rename('financial_adjustments', 'financial_adjustments_missing_bak');

        try {
            $this->actingAs($user)
                ->get(route('couriers.earnings.show', $line->id))
                ->assertOk()
                ->assertSee('Hakediş Detayı')
                ->assertSee('Migrasyon Kurye');
        } finally {
            if (\Illuminate\Support\Facades\Schema::hasTable('financial_adjustments_missing_bak')) {
                \Illuminate\Support\Facades\Schema::rename('financial_adjustments_missing_bak', 'financial_adjustments');
            }
        }
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
