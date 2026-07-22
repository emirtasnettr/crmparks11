<?php

namespace Tests\Feature;

use App\Core\Enums\Status;
use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftAttendance;
use App\Modules\ShiftPlanning\Services\AttendanceEarningSyncService;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityDeactivateTest extends TestCase
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

    public function test_super_admin_can_deactivate_business_courier_and_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        $courier = Courier::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        $agency = Agency::factory()->create(['status' => 'active', 'created_by' => $user->id]);

        $this->actingAs($user)->post(route('businesses.deactivate', $business->id), [
            'contract_end_date' => now()->toDateString(),
            'notes' => 'Sözleşme sonlandırıldı',
        ])
            ->assertRedirect(route('businesses.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $business->fresh()->status);
        $this->assertSame(now()->toDateString(), $business->fresh()->contract_end_date?->toDateString());

        $this->actingAs($user)->post(route('couriers.deactivate', $courier->id))
            ->assertRedirect(route('couriers.index'));

        $this->actingAs($user)->post(route('agencies.deactivate', $agency->id))
            ->assertRedirect(route('agencies.index'));

        $this->assertSame('inactive', $courier->fresh()->status);
        $this->assertSame('inactive', $agency->fresh()->status);
    }

    public function test_deactivating_business_or_courier_does_not_mutate_earnings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $business = Business::factory()->create(['status' => 'active', 'created_by' => $user->id]);
        $courier = Courier::factory()->create(['status' => 'active', 'created_by' => $user->id]);

        $statusId = EarningStatus::query()->where('code', 'pending_review')->value('id')
            ?? EarningStatus::query()->where('code', 'draft')->value('id');

        $line = EarningLine::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'created_by' => $user->id,
            'status_id' => $statusId,
            'description' => AttendanceEarningSyncService::DESCRIPTION_PREFIX.' Saatlik vardiya hakedişi (4 sa)',
            'pricing_model' => 'hourly',
            'earning_type' => 'hourly',
            'package_count' => 0,
            'worked_hours' => 4,
            'revenue_unit_price' => 100,
            'revenue_total' => 400,
            'courier_unit_price' => 80,
            'courier_total' => 320,
            'net_courier_payment' => 320,
            'profit' => 80,
            'period_month' => (int) now()->format('n'),
            'period_year' => (int) now()->format('Y'),
            'work_date' => now()->toDateString(),
        ]);

        $snapshot = $line->only([
            'id',
            'business_id',
            'courier_id',
            'status_id',
            'description',
            'pricing_model',
            'package_count',
            'worked_hours',
            'revenue_total',
            'courier_total',
            'net_courier_payment',
            'profit',
            'period_month',
            'period_year',
        ]);

        $this->actingAs($user)->post(route('businesses.deactivate', $business->id), [
            'contract_end_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('couriers.deactivate', $courier->id))
            ->assertRedirect();

        $this->assertSame('inactive', $business->fresh()->status);
        $this->assertSame('inactive', $courier->fresh()->status);

        $line->refresh();
        foreach ($snapshot as $key => $value) {
            $this->assertEquals($value, $line->getAttribute($key), "Earning field {$key} changed after deactivate");
        }

        $this->assertSame(1, EarningLine::query()->count());

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Test',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        BusinessShiftAttendance::query()->create([
            'business_shift_id' => $shift->id,
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'work_date' => now()->toDateString(),
            'started_at' => now()->subHours(2),
            'ended_at' => now(),
            'status' => 'completed',
            'worked_minutes' => 120,
            'hourly_rate' => 80,
            'earnings_amount' => 160,
            'pricing_model' => 'hourly',
        ]);

        $result = app(AttendanceEarningSyncService::class)->sync($user, [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
        ]);

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['updated']);

        $line->refresh();
        foreach ($snapshot as $key => $value) {
            $this->assertEquals($value, $line->getAttribute($key), "Earning field {$key} changed after sync on inactive entities");
        }
    }

    public function test_super_admin_can_suspend_and_deactivate_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $target = User::factory()->create(['status' => Status::Active]);
        $target->assignRole('operations_specialist');

        $this->actingAs($admin)->post(route('users.suspend', $target->id))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertSame(Status::Suspended, $target->fresh()->status);

        $this->actingAs($admin)->post(route('users.deactivate', $target->id))
            ->assertRedirect(route('users.index'));

        $this->assertSame(Status::Inactive, $target->fresh()->status);
    }
}
