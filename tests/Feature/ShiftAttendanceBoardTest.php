<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
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

class ShiftAttendanceBoardTest extends TestCase
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

    public function test_attendance_board_shows_expected_and_missing_couriers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Beklenen Kurye']);
        $this->assignCourier($business, $courier, $user);

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

        $this->actingAs($user)
            ->get(route('shift-planning.attendance'))
            ->assertOk()
            ->assertSee('Beklenen Kurye')
            ->assertSee('Girmedi')
            ->assertSee('Canlı Operasyon')
            ->assertDontSee('Vardiyasına girmemiş')
            ->assertDontSee('Tüm işletmeler');
    }

    public function test_upcoming_shift_today_shows_waiting_not_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Akşam Kurye']);
        $this->assignCourier($business, $courier, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '18:00',
            'end_time' => '23:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('shift-planning.attendance'));

        $response->assertOk()
            ->assertSee('Akşam Kurye')
            ->assertSee('Bekliyor')
            ->assertDontSee('Vardiyasına girmemiş')
            ->assertDontSee('Geç başlayanlar');
    }

    public function test_shift_within_one_hour_shows_starting_soon_label(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 17:30:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Yaklaşan Kurye']);
        $this->assignCourier($business, $courier, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '18:00',
            'end_time' => '23:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $this->actingAs($user)
            ->get(route('shift-planning.attendance'))
            ->assertOk()
            ->assertSee('Yaklaşan Kurye')
            ->assertSee('1 saat kaldı');
    }

    public function test_staff_can_start_and_end_attendance_for_courier(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 17:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Müdahale Kurye']);
        $this->assignCourier($business, $courier, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '16:00',
            'end_time' => '23:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $date = now()->toDateString();

        $this->actingAs($user)
            ->post(route('shift-planning.attendance.start'), [
                'business_id' => $business->id,
                'shift_id' => $shift->id,
                'courier_id' => $courier->id,
                'work_date' => $date,
            ])
            ->assertRedirect(route('shift-planning.attendance'));

        $attendance = BusinessShiftAttendance::query()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('in_progress', $attendance->status);
        $this->assertStringContainsString('Personel müdahalesi', (string) $attendance->notes);

        $this->actingAs($user)
            ->post(route('shift-planning.attendance.end'), [
                'business_id' => $business->id,
                'attendance_id' => $attendance->id,
                'work_date' => $date,
            ])
            ->assertRedirect(route('shift-planning.attendance'));

        $attendance->refresh();
        $this->assertSame('completed', $attendance->status);
        $this->assertNotNull($attendance->ended_at);
        $this->assertSame(420, (int) $attendance->worked_minutes);
    }

    public function test_weekly_calendar_includes_attendance_summary_label(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $this->assignCourier($business, $courier, $user);

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

        $this->actingAs($user)
            ->get(route('shift-planning.index', [
                'business_id' => $business->id,
                'week' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('gelmedi')
            ->assertSee('Canlı Operasyon');
    }

    public function test_courier_show_attendance_tab_accepts_date_filter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Filtre Kurye']);
        $this->assignCourier($business, $courier, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => '2026-06-10',
            'started_at' => '2026-06-10 10:05:00',
            'ended_at' => '2026-06-10 16:00:00',
            'status' => 'completed',
            'worked_minutes' => 355,
            'hourly_rate' => 100,
            'earnings_amount' => 591.67,
            'pricing_model' => 'hourly',
        ]);

        $this->actingAs($user)
            ->get(route('couriers.show', [
                'id' => $courier->id,
                'tab' => 'shift_earnings',
                'attendance_from' => '2026-06-01',
                'attendance_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Vardiya Katılımları')
            ->assertSee('10.06.2026');
    }

    private function createBusiness(User $user, array $overrides = []): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()->where('city_id', $city->id)->where('name', 'Kadıköy')->firstOrFail();

        return Business::factory()->create(array_merge([
            'created_by' => $user->id,
            'city_id' => $city->id,
            'district_id' => $district->id,
            'brand_name' => 'Test Market',
            'company_name' => 'Test Market A.Ş.',
            'status' => 'active',
        ], $overrides));
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

    private function assignCourier(Business $business, Courier $courier, User $user): void
    {
        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'assigned_by' => $user->id,
        ]);
    }
}
