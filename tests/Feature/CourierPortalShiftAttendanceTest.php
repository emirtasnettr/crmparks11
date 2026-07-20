<?php

namespace Tests\Feature;

use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierUserProvisioner;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Carbon\Carbon;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierPortalShiftAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        // Vardiya 16:00–23:00; 15 dk erken başlatma penceresi içinde.
        Carbon::setTestNow(Carbon::parse('2026-07-17 16:05:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_courier_can_start_and_end_assigned_shift(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $courier = Courier::factory()->create([
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        $user = app(CourierUserProvisioner::class)->ensureForCourier($courier);

        $business = Business::factory()->create([
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        $hourlyType = PricingModelType::query()->where('code', 'hourly')->firstOrFail();
        $business->activePricing()?->update([
            'pricing_model_type_id' => $hourlyType->id,
            'customer_unit_price' => 200,
            'courier_unit_price' => 150,
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '16:00',
            'end_time' => '23:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $this->actingAs($user)
            ->post(route('courier-portal.shifts.start', $shift->id))
            ->assertRedirect(route('courier-portal.dashboard'));

        $attendance = BusinessShiftAttendance::query()->first();
        $this->assertNotNull($attendance);
        $this->assertSame('in_progress', $attendance->status);
        $this->assertSame('hourly', $attendance->pricing_model);
        $this->assertEquals(150.0, (float) $attendance->hourly_rate);

        Carbon::setTestNow(Carbon::parse('2026-07-17 18:00:00'));

        $this->actingAs($user)
            ->post(route('courier-portal.shifts.end', $attendance->id))
            ->assertRedirect(route('courier-portal.dashboard'));

        $attendance->refresh();
        $this->assertSame('completed', $attendance->status);
        $this->assertNotNull($attendance->ended_at);
        // Hakediş = planlanan vardiya süresi (7 sa), erken/geç buffer yok.
        $this->assertSame(420, (int) $attendance->worked_minutes);
        $this->assertEquals(1050.0, (float) $attendance->earnings_amount);
    }

    public function test_courier_cannot_start_more_than_15_minutes_early(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 15:40:00'));

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
        $user = app(CourierUserProvisioner::class)->ensureForCourier($courier);
        $business = Business::factory()->create(['created_by' => $admin->id, 'status' => 'active']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '16:00',
            'end_time' => '23:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);

        $this->actingAs($user)
            ->from(route('courier-portal.dashboard'))
            ->post(route('courier-portal.shifts.start', $shift->id))
            ->assertRedirect(route('courier-portal.dashboard'))
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('business_shift_attendances', 0);
    }

    public function test_system_auto_ends_shift_30_minutes_after_end_using_scheduled_hours(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $courier = Courier::factory()->create(['created_by' => $admin->id, 'status' => 'active']);
        $business = Business::factory()->create(['created_by' => $admin->id, 'status' => 'active']);

        $hourlyType = PricingModelType::query()->where('code', 'hourly')->firstOrFail();
        $business->activePricing()?->update([
            'pricing_model_type_id' => $hourlyType->id,
            'courier_unit_price' => 100,
            'customer_unit_price' => 150,
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '10:00',
            'end_time' => '16:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $attendance = BusinessShiftAttendance::query()->create([
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

        // Bitiş 16:00 + 30 dk = 16:30
        Carbon::setTestNow(Carbon::parse('2026-07-17 16:31:00'));

        $ended = app(ShiftAttendanceService::class)->autoEndOverdueAttendances();
        $this->assertSame(1, $ended);

        $attendance->refresh();
        $this->assertSame('completed', $attendance->status);
        $this->assertSame(360, (int) $attendance->worked_minutes);
        $this->assertEquals(600.0, (float) $attendance->earnings_amount);
        $this->assertStringContainsString('Sistem otomatik sonlandırdı', (string) $attendance->notes);
        $this->assertSame('2026-07-17 16:00:00', $attendance->ended_at?->format('Y-m-d H:i:s'));
    }

    public function test_courier_cannot_start_unassigned_shift(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $courier = Courier::factory()->create([
            'created_by' => $admin->id,
            'status' => 'active',
        ]);
        $user = app(CourierUserProvisioner::class)->ensureForCourier($courier);

        $other = Courier::factory()->create([
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        $business = Business::factory()->create([
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '12:00',
            'end_time' => '16:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $other->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-17 12:10:00'));

        $this->actingAs($user)
            ->from(route('courier-portal.dashboard'))
            ->post(route('courier-portal.shifts.start', $shift->id))
            ->assertRedirect(route('courier-portal.dashboard'))
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('business_shift_attendances', 0);
    }
}
