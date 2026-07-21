<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCommercialContractTest extends TestCase
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

    public function test_creating_new_contract_ends_previous_without_mutating_amounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $first = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'business_amount' => 100,
            'courier_amount' => 70,
            'net_profit' => 30,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('businesses.commercial-contracts.store'), [
            'business_id' => $business->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'work_type' => 'per_package',
            'business_amount' => 50,
            'courier_amount' => 35,
            'payment_period' => 'weekly',
            'guaranteed_hourly_package_fee' => 80,
            'guaranteed_package_count' => 40,
        ])->assertRedirect();

        $first->refresh();
        $this->assertSame('ended', $first->status);
        $this->assertSame('2026-07-31', $first->end_date?->toDateString());
        $this->assertEquals(100.0, (float) $first->business_amount);
        $this->assertEquals(70.0, (float) $first->courier_amount);

        $second = BusinessCommercialContract::query()
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($second);
        $this->assertSame('per_package', $second->work_type);
        $this->assertSame($first->id, $second->supersedes_id);
        $this->assertEquals(80.0, (float) $second->guaranteed_hourly_package_fee);
        $this->assertSame(40, (int) $second->guaranteed_package_count);
    }

    public function test_super_admin_can_update_active_contract_amounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $contract = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'business_amount' => 100,
            'courier_amount' => 70,
            'net_profit' => 30,
            'payment_period' => 'monthly',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('businesses.commercial-contracts.update', $contract->id), [
            'start_date' => '2026-07-01',
            'end_date' => null,
            'work_type' => 'hourly',
            'business_amount' => 220,
            'courier_amount' => 150,
            'payment_period' => 'weekly',
            'notes' => 'Süper admin düzeltmesi',
        ])->assertRedirect(route('businesses.show', ['id' => $business->id, 'tab' => 'commercial-contracts']));

        $contract->refresh();
        $this->assertEquals(220.0, (float) $contract->business_amount);
        $this->assertEquals(150.0, (float) $contract->courier_amount);
        $this->assertEquals(70.0, (float) $contract->net_profit);
        $this->assertSame('weekly', $contract->payment_period);
        $this->assertSame('Süper admin düzeltmesi', $contract->notes);
        $this->assertSame('active', $contract->status);
    }

    public function test_non_super_admin_cannot_update_commercial_contract(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');
        $business = $this->createBusiness($user);

        $contract = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'business_amount' => 100,
            'courier_amount' => 70,
            'net_profit' => 30,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('businesses.commercial-contracts.update', $contract->id), [
            'start_date' => '2026-07-01',
            'work_type' => 'hourly',
            'business_amount' => 999,
            'courier_amount' => 1,
            'payment_period' => 'monthly',
        ])->assertForbidden();

        $contract->refresh();
        $this->assertEquals(100.0, (float) $contract->business_amount);
    }

    public function test_guarantee_fee_only_applies_to_per_package_contracts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $this->actingAs($user)->post(route('businesses.commercial-contracts.store'), [
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'work_type' => 'hourly',
            'business_amount' => 180,
            'courier_amount' => 120,
            'payment_period' => 'biweekly',
            'guaranteed_hourly_package_fee' => 85,
        ])->assertRedirect();

        $hourly = BusinessCommercialContract::query()
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($hourly);
        $this->assertSame('hourly', $hourly->work_type);
        $this->assertNull($hourly->guaranteed_hourly_package_fee);
        $this->assertEquals(120.0, $hourly->courierHourlyRateForAttendance());

        $this->actingAs($user)->post(route('businesses.commercial-contracts.store'), [
            'business_id' => $business->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'work_type' => 'per_package',
            'business_amount' => 48,
            'courier_amount' => 34,
            'payment_period' => 'weekly',
            'guaranteed_hourly_package_fee' => 90,
            'guaranteed_package_count' => 55,
        ])->assertRedirect();

        $perPackage = BusinessCommercialContract::query()
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($perPackage);
        $this->assertSame('per_package', $perPackage->work_type);
        $this->assertEquals(90.0, (float) $perPackage->guaranteed_hourly_package_fee);
        $this->assertSame(55, (int) $perPackage->guaranteed_package_count);
    }

    public function test_attendance_earnings_follow_contract_active_on_work_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-15',
            'business_amount' => 120,
            'courier_amount' => 60,
            'net_profit' => 60,
            'status' => 'ended',
            'created_by' => $user->id,
        ]);

        BusinessCommercialContract::factory()->perPackage()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-16',
            'end_date' => null,
            'business_amount' => 40,
            'courier_amount' => 28,
            'net_profit' => 12,
            'guaranteed_hourly_package_fee' => 90,
            'guaranteed_package_count' => 40,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        /** @var ShiftAttendanceService $service */
        $service = app(ShiftAttendanceService::class);

        Carbon::setTestNow(Carbon::parse('2026-07-10 09:05:00'));
        $july10 = $service->start($courier, $shift->id, Carbon::parse('2026-07-10'), [
            'started_at' => Carbon::parse('2026-07-10 09:00:00'),
            'staff_assist' => true,
        ]);
        $july10 = $service->end($courier, $july10->id, [
            'staff_assist' => true,
        ]);

        $this->assertSame('hourly', $july10->pricing_model);
        $this->assertEquals(60.0, (float) $july10->hourly_rate);
        $this->assertEquals(240.0, (float) $july10->earnings_amount); // 4h * 60

        Carbon::setTestNow(Carbon::parse('2026-07-20 13:05:00'));
        $july20 = $service->start($courier, $shift->id, Carbon::parse('2026-07-20'), [
            'started_at' => Carbon::parse('2026-07-20 09:00:00'),
            'staff_assist' => true,
        ]);
        $july20 = $service->end($courier, $july20->id, [
            'package_count' => 12,
            'latitude' => 41.0082,
            'longitude' => 28.9784,
        ]);

        $this->assertSame('per_package', $july20->pricing_model);
        $this->assertSame(12, (int) $july20->package_count);
        $this->assertEquals(336.0, (float) $july20->earnings_amount); // 12 * 28

        // Eski kaydın tutarı yeni kontrattan etkilenmez.
        $july10->refresh();
        $this->assertEquals(240.0, (float) $july10->earnings_amount);

        Carbon::setTestNow();
    }

    public function test_business_show_includes_commercial_contracts_tab(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('businesses.show', $business->id))
            ->assertOk()
            ->assertSee('Kontrat')
            ->assertSee('Aktif kontrat');
    }

    private function createBusiness(User $user): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'status' => 'active',
        ]);

        // Factory'nin otomatik oluşturduğu kontratı kaldır; test kendi kontratını kurar.
        $business->commercialContracts()->forceDelete();

        return $business->fresh();
    }

    private function createCourier(User $user): Courier
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        return Courier::factory()->create([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'status' => 'active',
        ]);
    }
}
