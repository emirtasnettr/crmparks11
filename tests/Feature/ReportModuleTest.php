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
            'start_time' => $now->copy()->subHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(3)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 2,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $soonShift = BusinessShift::query()->create([
            'business_id' => $business->id,
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
        $response->assertSee('Canlı Operasyon');
        $response->assertSee('Radar İşletme');
        $response->assertSee('Gerekli Kişi');
        $response->assertSee('Atanan Kurye');
        $response->assertSee('Eksik Kurye');
        $response->assertSee('Aktif Kurye');
        $response->assertSee('Yaklaşan Kurye Adı');
        $response->assertSee('Bugünkü vardiyalar');
        $response->assertDontSee('7 günlük vardiya planı');
        $response->assertSee(route('radar'), false);
    }

    public function test_sales_manager_can_view_radar_and_reports_placeholder(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales_manager');

        $this->actingAs($user)->get(route('radar'))
            ->assertOk()
            ->assertSee('Canlı Operasyon');

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Yapım aşamasında');
    }

    public function test_radar_uses_required_headcount_for_missing_and_today_shift_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Kadro İşletme',
        ]);

        $courierA = Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Kurye Bir']);
        $courierB = Courier::factory()->create(['created_by' => $user->id, 'full_name' => 'Kurye İki']);

        $now = now()->seconds(0);
        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => $now->copy()->subHours(2)->format('H:i'),
            'end_time' => $now->copy()->addHours(3)->format('H:i'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 3,
            'days_of_week' => [(int) now()->dayOfWeek],
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
            'work_date' => Carbon::today()->toDateString(),
            'started_at' => now()->subHour(),
            'status' => 'in_progress',
            'worked_minutes' => 0,
            'hourly_rate' => 200,
            'earnings_amount' => null,
        ]);

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['shift_count']);
        $this->assertSame(3, $row['required_count']);
        $this->assertSame(2, $row['assigned_count']);
        $this->assertSame(1, $row['started_count']);
        $this->assertSame(1, $row['missing_assignments']);
        $this->assertSame(2, $row['missing_count']);
        $this->assertSame(2, $row['operational_shortage']);

        $shiftDetail = $row['today_shifts'][0];
        $this->assertNotEmpty($shiftDetail['time'] ?? null);
        $this->assertSame(3, $shiftDetail['required']);
        $this->assertSame(2, $shiftDetail['assigned']);
        $this->assertSame(1, $shiftDetail['started']);
        $this->assertSame(1, $shiftDetail['missing_assignments']);
        $this->assertSame(1, $shiftDetail['assigned_not_started']);
        $this->assertSame(2, $shiftDetail['operational_shortage']);
        $this->assertStringContainsString('1/3 geldi', $shiftDetail['summary_label']);
        $this->assertStringContainsString('2 eksik', $shiftDetail['summary_label']);
        $this->assertStringContainsString('1 katılmadı', $shiftDetail['summary_label']);
        $this->assertArrayNotHasKey('week_schedule', $row);
    }

    public function test_radar_counts_assigned_couriers_even_after_shift_start(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Atanan İşletme',
        ]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => 'Atanan Test Kurye',
        ]);

        $now = now()->seconds(0);
        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
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
        $this->assertSame(1, $row['required_count']);
        $this->assertSame(0, $row['started_count']);
        $this->assertSame(1, $row['assigned_count']);
        $this->assertSame(1, $row['missing_count']);
        $this->assertSame(1, $row['operational_shortage']);
        $this->assertSame('Atanan Test Kurye', $row['today_shifts'][0]['couriers'][0]['name'] ?? null);
        $this->assertSame('not_started', $row['today_shifts'][0]['couriers'][0]['status'] ?? null);
        $this->assertNotEmpty($row['today_shifts'][0]['time'] ?? null);
    }

    public function test_radar_expand_shows_only_today_shifts_not_week_plan(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Bugünlük Radar',
        ]);
        $courier = Courier::factory()->create([
            'created_by' => $user->id,
            'full_name' => 'Bugün Kurye',
        ]);

        $tomorrowDow = (int) now()->addDay()->dayOfWeek;

        $todayShift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [(int) now()->dayOfWeek],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShift::query()->create([
            'business_id' => $business->id,
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
            'business_shift_id' => $todayShift->id,
            'courier_id' => $courier->id,
        ]);

        $radar = app(\App\Modules\Report\Services\ReportService::class)->radar();
        $row = collect($radar['rows'])->firstWhere('business_id', $business->id);

        $this->assertNotNull($row);
        $this->assertCount(1, $row['today_shifts']);
        $this->assertStringContainsString('10:00', (string) ($row['today_shifts'][0]['time'] ?? ''));
        $this->assertSame('Bugün Kurye', $row['today_shifts'][0]['couriers'][0]['name'] ?? null);
        $this->assertFalse(
            collect($row['today_shifts'])->contains(
                fn (array $shift) => str_contains((string) ($shift['time'] ?? ''), '12:00')
            )
        );
    }

    public function test_radar_only_lists_businesses_with_shifts_today(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $withShift = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Bugün Vardiyalı',
        ]);
        $withoutShift = Business::factory()->create([
            'created_by' => $user->id,
            'brand_name' => 'Vardiyasız İşletme',
        ]);

        $courier = Courier::factory()->create(['created_by' => $user->id]);

        BusinessShift::query()->create([
            'business_id' => $withShift->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'required_headcount' => 1,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShift::query()->create([
            'business_id' => $withoutShift->id,
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
