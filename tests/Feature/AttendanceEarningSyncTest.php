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
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
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

        $this->assertSame(4, $result['created']);
        $this->assertSame(0, $result['updated']);

        $lines = EarningLine::query()
            ->where('courier_id', $courier->id)
            ->orderBy('work_date')
            ->get();

        $this->assertCount(4, $lines);
        $this->assertEquals([
            '2026-07-10',
            '2026-07-11',
            '2026-07-15',
            '2026-07-16',
        ], $lines->map(fn (EarningLine $line) => $line->work_date?->toDateString())->all());

        $hourlyLines = $lines->where('pricing_model', 'hourly');
        $packageLines = $lines->where('pricing_model', 'per_package');

        $this->assertCount(2, $hourlyLines);
        $this->assertCount(2, $packageLines);

        // Her gün 6h × unit
        $this->assertEquals(1320.0, (float) $hourlyLines->first()->net_courier_payment);
        $this->assertEquals(6.0, (float) $hourlyLines->first()->worked_hours);
        $this->assertEquals(1350.0, (float) $packageLines->first()->net_courier_payment);
        $this->assertEquals(6.0, (float) $packageLines->first()->worked_hours);

        foreach ($lines as $line) {
            $this->assertStringStartsWith(AttendanceEarningSyncService::DESCRIPTION_PREFIX, (string) $line->description);
        }

        // Idempotent re-run updates drafts
        $again = app(AttendanceEarningSyncService::class)->sync($admin);
        $this->assertSame(0, $again['created']);
        $this->assertSame(4, $again['updated']);
        $this->assertSame(4, EarningLine::query()->where('courier_id', $courier->id)->count());
    }

    public function test_sync_does_not_duplicate_same_day_on_repeated_runs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = $this->createCourier($admin);
        $business = $this->createBusiness($admin, 'Tek Gün');

        $contract = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'business_amount' => 150,
            'courier_amount' => 100,
            'net_profit' => 50,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $shift = $this->createShift($business, $courier, $admin);
        $this->seedAttendance($shift, $business, $contract, $courier, '2026-07-17', 'hourly', 100, 6 * 60);

        $sync = app(AttendanceEarningSyncService::class);
        $this->assertSame(1, $sync->sync($admin)['created']);
        $this->assertSame(0, $sync->sync($admin)['created']);
        $this->assertSame(1, $sync->sync($admin)['updated']);

        $this->assertSame(1, EarningLine::query()
            ->where('courier_id', $courier->id)
            ->whereDate('work_date', '2026-07-17')
            ->count());
    }

    public function test_dedupe_removes_duplicate_sync_lines_for_same_day(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = $this->createCourier($admin);
        $business = $this->createBusiness($admin, 'Kopya Gün');

        $statusId = \App\Models\EarningStatus::query()->where('code', 'pending_review')->value('id');

        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'pricing_model' => 'hourly',
            'work_date' => '2026-07-17',
            'period_month' => 7,
            'period_year' => 2026,
            'description' => AttendanceEarningSyncService::DESCRIPTION_PREFIX.' Saatlik vardiya hakedişi (6 sa)',
            'status_id' => $statusId,
            'net_courier_payment' => 600,
            'courier_total' => 600,
            'created_by' => $admin->id,
        ]);
        EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'pricing_model' => 'hourly',
            'work_date' => '2026-07-17',
            'period_month' => 7,
            'period_year' => 2026,
            'description' => AttendanceEarningSyncService::DESCRIPTION_PREFIX.' Saatlik vardiya hakedişi (6 sa)',
            'status_id' => $statusId,
            'net_courier_payment' => 600,
            'courier_total' => 600,
            'created_by' => $admin->id,
        ]);

        $result = app(AttendanceEarningSyncService::class)->dedupeSyncLines();

        $this->assertSame(1, $result['groups']);
        $this->assertSame(1, $result['removed']);
        $this->assertSame(1, EarningLine::query()
            ->where('courier_id', $courier->id)
            ->whereDate('work_date', '2026-07-17')
            ->count());
    }

    public function test_artisan_sync_command_runs(): void
    {
        $this->artisan('crmlog:earnings:sync-from-attendance')
            ->assertSuccessful();
    }

    public function test_completing_attendance_auto_creates_earning_line_from_contract(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = $this->createCourier($admin);
        $business = $this->createBusiness($admin, 'Otomatik Hakediş');

        BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'business_amount' => 150,
            'courier_amount' => 100,
            'net_profit' => 50,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $shift = $this->createShift($business, $courier, $admin);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-17',
            'started_at' => '2026-07-17 09:50:00',
            'status' => 'in_progress',
            'worked_minutes' => 0,
            'hourly_rate' => 100,
            'pricing_model' => 'hourly',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-17 16:16:00'));

        $ended = app(ShiftAttendanceService::class)
            ->autoEndOverdueAttendances();

        $this->assertSame(1, $ended);

        $line = EarningLine::query()
            ->where('courier_id', $courier->id)
            ->where('business_id', $business->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertStringStartsWith(AttendanceEarningSyncService::DESCRIPTION_PREFIX, (string) $line->description);
        // 6h × 100 = 600
        $this->assertEquals(600.0, (float) $line->net_courier_payment);
        $this->assertEquals(6.0, (float) $line->worked_hours);
    }

    public function test_retrospective_materialize_auto_creates_earning_line(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-20 12:00:00'));

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $courier = $this->createCourier($admin);
        $business = $this->createBusiness($admin, 'Retrospektif Hakediş');

        BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'business_amount' => 150,
            'courier_amount' => 110,
            'net_profit' => 40,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'required_headcount' => 1,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $created = app(ShiftAttendanceService::class)
            ->materializeRetrospectiveCompletions($shift);

        $this->assertSame(1, $created);

        $line = EarningLine::query()
            ->where('courier_id', $courier->id)
            ->where('business_id', $business->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertEquals(660.0, (float) $line->net_courier_payment);
        $this->assertEquals(6.0, (float) $line->worked_hours);
    }

    public function test_sync_backfills_earnings_from_contract_when_missing(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = $this->createCourier($admin);
        $business = $this->createBusiness($admin, 'Backfill Marka');

        $contract = BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'business_amount' => 250,
            'courier_amount' => 200,
            'net_profit' => 50,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $shift = $this->createShift($business, $courier, $admin);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'commercial_contract_id' => $contract->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-10',
            'started_at' => '2026-07-10 10:00:00',
            'ended_at' => '2026-07-10 16:00:00',
            'status' => 'completed',
            'worked_minutes' => 360,
            'hourly_rate' => null,
            'earnings_amount' => null,
            'pricing_model' => null,
        ]);

        $result = app(AttendanceEarningSyncService::class)->sync($admin);

        $this->assertSame(1, $result['created']);

        $attendance = BusinessShiftAttendance::query()->first();
        $this->assertEquals(200.0, (float) $attendance->hourly_rate);
        $this->assertEquals(1200.0, (float) $attendance->earnings_amount);
        $this->assertSame('hourly', $attendance->pricing_model);

        $line = EarningLine::query()->where('courier_id', $courier->id)->first();
        $this->assertNotNull($line);
        $this->assertEquals(1200.0, (float) $line->net_courier_payment);
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
