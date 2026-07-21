<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_reports_index_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('radar'))->assertForbidden();
    }

    public function test_reports_index_shows_under_construction(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Raporlar')
            ->assertSee('Yapım aşamasında')
            ->assertDontSee('Planlanmış Kurye');
    }

    public function test_super_admin_can_view_radar_report(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Radar İşletme',
            'planned_courier_count' => 6,
        ]);
        $courierA = Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Aktif Kurye']);
        $courierB = Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Yaklaşan Kurye Adı']);

        $now = now()->seconds(0);
        $currentShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Gündüz',
            'start_time' => $now->copy()->subHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(3)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $soonShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => $now->copy()->addHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(6)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $currentShift->id,
            'courier_id' => $courierA->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $soonShift->id,
            'courier_id' => $courierB->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $currentShift->id,
            'business_id' => $business->id,
            'courier_id' => $courierA->id,
            'work_date' => Carbon::today()->toDateString(),
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
            'worked_minutes' => 0,
            'hourly_rate' => 220,
            'earnings_amount' => null,
        ]);

        $response = $this->actingAs($user)->get(route('radar'));

        $response->assertOk();
        $response->assertSee('Radar');
        $response->assertSee('Radar İşletme');
        $response->assertSee('Planlanmış Kurye');
        $response->assertSee('Vardiyada Kurye');
        $response->assertSee('Atanan Kurye');
        $response->assertSeeText('6');
        $response->assertSee('Aktif Kurye');
        $response->assertSee('Yaklaşan Kurye Adı');
        $response->assertSee(route('radar'), false);
    }

    public function test_sales_manager_can_view_radar_and_reports_placeholder(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        $this->actingAs($user)->get(route('radar'))
            ->assertOk()
            ->assertSee('Radar');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Yapım aşamasında');
    }

    public function test_radar_caps_active_and_upcoming_at_planned_courier_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Kapasite İşletme',
            'planned_courier_count' => 1,
        ]);

        $couriers = collect([
            Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Kurye Bir']),
            Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Kurye İki']),
            Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Kurye Üç']),
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Kapasite Vardiya',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'required_headcount' => 3,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        foreach ($couriers as $courier) {
            BusinessShiftCourier::query()->create([
                'business_shift_id' => $shift->id,
                'courier_id' => $courier->id,
            ]);

            BusinessShiftAttendance::query()->create([
                'business_shift_id' => $shift->id,
                'business_id' => $business->id,
                'courier_id' => $courier->id,
                'work_date' => Carbon::today()->toDateString(),
                'started_at' => now()->subHour(),
                'status' => 'in_progress',
                'worked_minutes' => 0,
                'hourly_rate' => 200,
                'earnings_amount' => null,
            ]);
        }

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['planned_courier_count']);
        $this->assertSame(1, $row['active_on_shift_count']);
        $this->assertSame(0, $row['roster_planned_count']);
        $this->assertSame(0, $row['missing_courier_count']);
        $this->assertSame(
            $row['planned_courier_count'],
            $row['active_on_shift_count'] + $row['roster_planned_count'] + $row['missing_courier_count']
        );
    }

    public function test_radar_active_plus_upcoming_never_exceeds_planned(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Döneristan Test',
            'planned_courier_count' => 8,
        ]);

        $activeCouriers = collect(range(1, 4))->map(fn (int $n) => Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => "Aktif Kurye {$n}",
        ]));
        $upcomingCouriers = collect(range(1, 3))->map(fn (int $n) => Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => "Yaklaşan Test {$n}",
        ]));

        $now = now()->seconds(0);
        $currentShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Şu Anki Vardiya',
            'start_time' => $now->copy()->subHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(3)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 4,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $soonShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Yaklaşan Vardiya',
            'start_time' => $now->copy()->addHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(6)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 3,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        foreach ($activeCouriers as $courier) {
            BusinessShiftCourier::query()->create([
                'business_shift_id' => $currentShift->id,
                'courier_id' => $courier->id,
            ]);
            BusinessShiftAttendance::query()->create([
                'business_shift_id' => $currentShift->id,
                'business_id' => $business->id,
                'courier_id' => $courier->id,
                'work_date' => Carbon::today()->toDateString(),
                'started_at' => now()->subHour(),
                'status' => 'in_progress',
                'worked_minutes' => 0,
                'hourly_rate' => 200,
                'earnings_amount' => null,
            ]);
        }

        foreach ($upcomingCouriers as $courier) {
            BusinessShiftCourier::query()->create([
                'business_shift_id' => $soonShift->id,
                'courier_id' => $courier->id,
            ]);
        }

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $this->assertSame(8, $row['planned_courier_count']);
        $this->assertSame(4, $row['active_on_shift_count']);
        $this->assertSame(3, $row['roster_planned_count']);
        $this->assertSame(1, $row['missing_courier_count']);
        $this->assertSame(
            $row['planned_courier_count'],
            $row['active_on_shift_count'] + $row['roster_planned_count'] + $row['missing_courier_count']
        );
    }

    public function test_radar_counts_assigned_couriers_even_after_shift_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Atanan İşletme',
            'planned_courier_count' => 1,
        ]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => 'Atanan Test Kurye',
        ]);

        $now = now()->seconds(0);
        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Başlamış Vardiya',
            'start_time' => $now->copy()->subHours(1)->format('H:i'),
            'end_time' => $now->copy()->addHours(4)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['planned_courier_count']);
        $this->assertSame(0, $row['active_on_shift_count']);
        $this->assertSame(1, $row['roster_planned_count']);
        $this->assertSame(0, $row['missing_courier_count']);
        $this->assertSame('Atanan Test Kurye', $row['roster_couriers'][0]['name'] ?? null);
        $this->assertNotEmpty($row['week_schedule'] ?? []);
        $this->assertSame('Başlamış Vardiya', $row['week_schedule'][0]['shifts'][0]['name'] ?? null);
        $this->assertSame('Atanan Test Kurye', $row['week_schedule'][0]['shifts'][0]['couriers'][0]['name'] ?? null);
    }

    public function test_radar_week_schedule_includes_upcoming_days(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Haftalık Radar',
            'planned_courier_count' => 2,
        ]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => 'Hafta Kurye',
        ]);

        $tomorrowDow = (int) now()->addDay()->dayOfWeek;

        BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Bugünlük',
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $tomorrowShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Yarınlık',
            'start_time' => '12:00',
            'end_time' => '20:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [$tomorrowDow],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $tomorrowShift->id,
            'courier_id' => $courier->id,
        ]);

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $labels = collect($row['week_schedule'])->pluck('label')->all();
        $this->assertTrue(collect($labels)->contains(fn (string $label) => str_starts_with($label, 'Bugün')));
        $this->assertTrue(collect($labels)->contains(fn (string $label) => str_starts_with($label, 'Yarın')));

        $tomorrowDay = collect($row['week_schedule'])->first(
            fn (array $day) => str_starts_with($day['label'], 'Yarın')
        );
        $this->assertNotNull($tomorrowDay);
        $this->assertSame('Yarınlık', $tomorrowDay['shifts'][0]['name'] ?? null);
        $this->assertSame('Hafta Kurye', $tomorrowDay['shifts'][0]['couriers'][0]['name'] ?? null);
    }

    public function test_radar_only_lists_businesses_with_shifts_today(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $withShift = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Bugün Vardiyalı',
            'planned_courier_count' => 4,
        ]);
        $withoutShift = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Vardiyasız İşletme',
            'planned_courier_count' => 5,
        ]);

        $courier = Courier::factory()->create(['created_by' => $user->id]);

        BusinessShift::query()->create([
            'business_id' => $withShift->id,
            'name' => 'Bugünkü',
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // Bu vardiya bugün çalışmıyor (haftanın diğer günü).
        BusinessShift::query()->create([
            'business_id' => $withoutShift->id,
            'name' => 'Başka Gün',
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [((int) now()->dayOfWeek + 1) % 7],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => BusinessShift::query()->where('business_id', $withShift->id)->value('id'),
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($user)->get(route('radar'));

        $response->assertOk();
        $response->assertSee('Bugün Vardiyalı');
        $response->assertDontSee('Vardiyasız İşletme');
    }
}
