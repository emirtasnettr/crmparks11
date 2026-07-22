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
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '16:00',
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

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '18:00',
            'end_time' => '23:00',
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
        $courier = $this->createCourier($user, ['full_name' => 'Saati Yakın Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '18:00',
            'end_time' => '23:00',
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

        $this->actingAs($user)
            ->get(route('shift-planning.attendance'))
            ->assertOk()
            ->assertSee('Saati Yakın Kurye')
            ->assertSee('Yaklaşan');
    }

    public function test_midday_unstarted_morning_shift_shows_not_started_not_starting_soon(): void
    {
        // Türkiye saati öğleden sonra; UTC olsaydı sabah vardiyası yanlışlıkla "Yaklaşan" görünürdü.
        Carbon::setTestNow(Carbon::parse('2026-07-17 11:40:00', 'Europe/Istanbul'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Girmeyen Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '09:00',
            'end_time' => '17:00',
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

        $this->actingAs($user)
            ->get(route('shift-planning.attendance'))
            ->assertOk()
            ->assertSee('Girmeyen Kurye')
            ->assertSee('Girmedi')
            ->assertDontSee('Yaklaşan');
    }

    public function test_staff_can_start_and_end_attendance_for_courier(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 17:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Müdahale Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '16:00',
            'end_time' => '23:00',
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

        $attendance->update(['started_at' => Carbon::parse('2026-07-17 16:00:00')]);

        $this->actingAs($user)
            ->post(route('shift-planning.attendance.end'), [
                'business_id' => $business->id,
                'attendance_id' => $attendance->id,
                'work_date' => $date,
                'ended_at' => '2026-07-17 23:00:00',
            ])
            ->assertRedirect(route('shift-planning.attendance'));

        $attendance->refresh();
        $this->assertSame('completed', $attendance->status);
        $this->assertNotNull($attendance->ended_at);
        $this->assertSame(420, (int) $attendance->worked_minutes);
    }

    public function test_staff_can_end_early_and_start_replacement_courier(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 10:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        BusinessCommercialContract::query()->where('business_id', $business->id)->delete();
        BusinessCommercialContract::factory()->hourly()->create([
            'business_id' => $business->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
            'business_amount' => 200,
            'courier_amount' => 100,
            'net_profit' => 100,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $ahmet = $this->createCourier($user, ['full_name' => 'Ahmet Kurye']);
        $mehmet = $this->createCourier($user, ['full_name' => 'Mehmet Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $ahmet->id,
        ]);

        $this->actingAs($user)
            ->post(route('shift-planning.attendance.start'), [
                'business_id' => $business->id,
                'shift_id' => $shift->id,
                'courier_id' => $ahmet->id,
                'work_date' => '2026-07-17',
            ])
            ->assertRedirect();

        $attendance = BusinessShiftAttendance::query()->where('courier_id', $ahmet->id)->first();
        $attendance->update(['started_at' => Carbon::parse('2026-07-17 09:00:00')]);

        $this->actingAs($user)
            ->from(route('shift-planning.attendance'))
            ->post(route('shift-planning.attendance.end'), [
                'business_id' => $business->id,
                'attendance_id' => $attendance->id,
                'work_date' => '2026-07-17',
                'ended_at' => '2026-07-17 10:00:00',
                'end_reason' => 'accident',
                'replacement_courier_id' => $mehmet->id,
            ])
            ->assertRedirect(route('shift-planning.attendance'))
            ->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame('completed', $attendance->status);
        $this->assertSame(60, (int) $attendance->worked_minutes);
        $this->assertSame('accident', $attendance->end_reason);
        $this->assertNotNull($attendance->replaced_by_attendance_id);
        $this->assertEquals(100.0, (float) $attendance->earnings_amount);

        $replacement = BusinessShiftAttendance::query()->where('courier_id', $mehmet->id)->first();
        $this->assertNotNull($replacement);
        $this->assertSame('in_progress', $replacement->status);
        $this->assertSame($attendance->id, (int) $replacement->replaces_attendance_id);
        $this->assertTrue(
            Carbon::parse($replacement->started_at)->equalTo(Carbon::parse('2026-07-17 10:00:00'))
        );

        $this->assertTrue(
            DB::table('business_shift_couriers')
                ->where('business_shift_id', $shift->id)
                ->where('courier_id', $mehmet->id)
                ->exists()
        );
    }

    public function test_staff_can_mark_missing_courier_as_attended_after_shift_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 23:30:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Gelmedi Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '16:00',
            'end_time' => '23:00',
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

        $this->actingAs($user)
            ->get(route('shift-planning.attendance'))
            ->assertOk()
            ->assertSee('Geldi');

        $this->actingAs($user)
            ->post(route('shift-planning.attendance.mark-attended'), [
                'business_id' => $business->id,
                'shift_id' => $shift->id,
                'courier_id' => $courier->id,
                'work_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('shift-planning.attendance'));

        $attendance = BusinessShiftAttendance::query()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('completed', $attendance->status);
        $this->assertSame(420, (int) $attendance->worked_minutes);
        $this->assertStringContainsString('geldi olarak işaretledi', (string) $attendance->notes);
    }

    public function test_weekly_calendar_includes_attendance_summary_label(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '16:00',
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

        $this->actingAs($user)
            ->get(route('shift-planning.index', [
                'business_id' => $business->id,
                'week' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('katılmadı')
            ->assertSee('Canlı Operasyon');
    }

    public function test_weekly_calendar_shows_assignment_label_before_shift_starts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 09:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '12:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
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
            ->assertSee('1/1 atandı')
            ->assertDontSee('katılmadı')
            ->assertDontSee('0/1 geldi');
    }

    public function test_weekly_calendar_counts_unfilled_slots_and_no_shows_against_required_headcount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 12:00:00'));

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courierA = $this->createCourier($user, ['full_name' => 'Kurye A']);
        $courierB = $this->createCourier($user, ['full_name' => 'Kurye B']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 3,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courierA->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courierB->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $courierA->id,
            'work_date' => now()->toDateString(),
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get(route('shift-planning.index', [
                'business_id' => $business->id,
                'week' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('1/3 geldi · 2 eksik · 1 katılmadı');
    }

    public function test_courier_show_attendance_tab_accepts_date_filter(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Filtre Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '16:00',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
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

}
