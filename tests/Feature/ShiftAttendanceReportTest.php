<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftAttendanceReportTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_shift_report_requires_date_and_lists_daily_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 14:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Rapor Kurye', 'phone' => '0555 111 22 33']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-07-17',
            'started_at' => '2026-07-17 10:21:00',
            'ended_at' => null,
            'status' => 'in_progress',
            'worked_minutes' => 0,
            'pricing_model' => 'hourly',
        ]);

        $this->actingAs($user)
            ->get(route('shift-planning.report', ['from' => '2026-07-17', 'to' => '2026-07-17']))
            ->assertOk()
            ->assertSee('Vardiya Raporu')
            ->assertSee('Rapor Kurye')
            ->assertSee('Geç - 21 dk')
            ->assertSee('0555 111 22 33');
    }

    public function test_shift_report_defaults_to_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 14:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('shift-planning.report'))
            ->assertOk()
            ->assertSee('name="from"', false)
            ->assertSee('value="2026-07-16"', false)
            ->assertSee('value="2026-07-16"', false);
    }

    public function test_shift_report_filters_by_multiple_statuses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 14:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $lateCourier = $this->createCourier($user, ['full_name' => 'Geç Kurye']);
        $missingCourier = $this->createCourier($user, ['full_name' => 'Girmedi Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'required_headcount' => 2,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $lateCourier->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $missingCourier->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $lateCourier->id,
            'work_date' => '2026-07-16',
            'started_at' => '2026-07-16 10:20:00',
            'ended_at' => '2026-07-16 16:00:00',
            'status' => 'completed',
            'worked_minutes' => 340,
            'pricing_model' => 'hourly',
        ]);

        $this->actingAs($user)
            ->get(route('shift-planning.report', [
                'from' => '2026-07-16',
                'to' => '2026-07-16',
                'status' => ['late'],
            ]))
            ->assertOk()
            ->assertSee('Geç Kurye')
            ->assertDontSee('Girmedi Kurye');
    }

    public function test_shift_report_excel_export_downloads(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 14:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Excel Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('shift-planning.report.export', ['from' => '2026-07-16', 'to' => '2026-07-17']));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type')
        );
    }

    private function createBusiness(User $user): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        return Business::factory()->create([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'brand_name' => 'Rapor Market',
            'company_name' => 'Rapor Market A.Ş.',
            'status' => 'active',
        ]);
    }

    private function createCourier(User $user, array $overrides = []): Courier
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'status' => 'active',
        ], $overrides));
    }

}
