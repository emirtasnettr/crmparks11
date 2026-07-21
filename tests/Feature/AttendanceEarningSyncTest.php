<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCommercialContract;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Services\AttendanceEarningSyncService;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceEarningSyncTest extends TestCase
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

    public function test_sync_creates_separate_lines_for_hourly_and_per_package_in_same_month(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = $this->createCourier($admin);

        $packageBusiness = $this->createBusiness($admin, 'Paket Marka');
        $hourlyBusiness = $this->createBusiness($admin, 'Saatlik Marka');

        $packageContract = BusinessCommercialContract::factory()->perPackage()->create([
            'business_id' => $packageBusiness->id,
            'start_date' => '2026-07-01',
            'business_amount' => 100,
            'courier_amount' => 90,
            'net_profit' => 10,
            'guaranteed_hourly_package_fee' => 225,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $hourlyContract = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $hourlyBusiness->id,
            'start_date' => '2026-07-01',
            'business_amount' => 250,
            'courier_amount' => 220,
            'net_profit' => 30,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $packageShift = $this->createShift($packageBusiness, $courier, $admin);
        $hourlyShift = $this->createShift($hourlyBusiness, $courier, $admin);

        // 2 days package (10–11 Jul), 2 days hourly (15–16 Jul)
        $this->seedAttendance($packageShift, $packageBusiness, $packageContract, $courier, '2026-07-10', 'per_package', 225, 6 * 60);
        $this->seedAttendance($packageShift, $packageBusiness, $packageContract, $courier, '2026-07-11', 'per_package', 225, 6 * 60);
        $this->seedAttendance($hourlyShift, $hourlyBusiness, $hourlyContract, $courier, '2026-07-15', 'hourly', 220, 6 * 60);
        $this->seedAttendance($hourlyShift, $hourlyBusiness, $hourlyContract, $courier, '2026-07-16', 'hourly', 220, 6 * 60);

        $result = app(AttendanceEarningSyncService::class)->sync($admin);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);

        $lines = EarningLine::query()
            ->where('courier_id', $courier->id)
            ->orderBy('pricing_model')
            ->get();

        $this->assertCount(2, $lines);

        $hourlyLine = $lines->firstWhere('pricing_model', 'hourly');
        $packageLine = $lines->firstWhere('pricing_model', 'per_package');

        $this->assertNotNull($hourlyLine);
        $this->assertNotNull($packageLine);
        $this->assertSame($hourlyBusiness->id, $hourlyLine->business_id);
        $this->assertSame($packageBusiness->id, $packageLine->business_id);
        $this->assertSame(7, $hourlyLine->period_month);
        $this->assertSame(2026, $hourlyLine->period_year);
        $this->assertNotNull($hourlyLine->work_date);

        // 2 × 6h × 220 = 2640
        $this->assertEquals(2640.0, (float) $hourlyLine->net_courier_payment);
        $this->assertEquals(12.0, (float) $hourlyLine->worked_hours);
        // 2 × 6h × 225 = 2700
        $this->assertEquals(2700.0, (float) $packageLine->net_courier_payment);
        $this->assertEquals(12.0, (float) $packageLine->worked_hours);

        $this->assertStringStartsWith(AttendanceEarningSyncService::DESCRIPTION_PREFIX, (string) $hourlyLine->description);
        $this->assertStringStartsWith(AttendanceEarningSyncService::DESCRIPTION_PREFIX, (string) $packageLine->description);

        // Idempotent re-run updates drafts
        $again = app(AttendanceEarningSyncService::class)->sync($admin);
        $this->assertSame(0, $again['created']);
        $this->assertSame(2, $again['updated']);
        $this->assertSame(2, EarningLine::query()->where('courier_id', $courier->id)->count());
    }

    public function test_artisan_sync_command_runs(): void
    {
        $this->artisan('crmlog:earnings:sync-from-attendance')
            ->assertSuccessful();
    }

    private function createBusiness(User $user, string $brand): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'brand_name' => $brand,
            'status' => 'active',
        ]);
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

    private function createShift(Business $business, Courier $courier, User $user): BusinessShift
    {
        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Test',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'required_headcount' => 1,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        return $shift;
    }

    private function seedAttendance(
        BusinessShift $shift,
        Business $business,
        BusinessCommercialContract $contract,
        Courier $courier,
        string $date,
        string $pricingModel,
        float $hourlyRate,
        int $workedMinutes,
    ): void {
        $day = Carbon::parse($date);
        $startedAt = $day->copy()->setTime(10, 0);
        $endedAt = $startedAt->copy()->addMinutes($workedMinutes);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'commercial_contract_id' => $contract->id,
            'courier_id' => $courier->id,
            'work_date' => $date,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => 'completed',
            'worked_minutes' => $workedMinutes,
            'hourly_rate' => $hourlyRate,
            'earnings_amount' => round(($workedMinutes / 60) * $hourlyRate, 2),
            'pricing_model' => $pricingModel,
        ]);
    }
}
