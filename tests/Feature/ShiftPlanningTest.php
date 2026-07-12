<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftDayCourier;
use Carbon\Carbon;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftPlanningTest extends TestCase
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

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('shift-planning.index'))
            ->assertForbidden();
    }

    public function test_index_shows_business_selector_and_empty_state(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createBusiness($user, ['brand_name' => 'Demo Market']);

        $response = $this->actingAs($user)->get(route('shift-planning.index'));

        $response->assertOk();
        $response->assertSee('Vardiya Planlama');
        $response->assertSee('önce bir işletme seçin', false);
        $response->assertSee('Demo Market');
    }

    public function test_can_create_shift_with_optional_couriers_for_all_days(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Zeynep Ak']);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'assigned_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Tam Gün',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-15',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ]);

        $shift = BusinessShift::query()->first();
        $this->assertNotNull($shift);
        $response->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));

        $occurrenceCount = count($shift->occurrenceDates());
        $this->assertSame(10, $occurrenceCount);
        $this->assertSame(
            $occurrenceCount,
            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->where('courier_id', $courier->id)
                ->count()
        );
    }

    public function test_can_create_shift_with_date_range(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $response = $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => '1',
        ]);

        $shift = BusinessShift::query()->first();

        $this->assertNotNull($shift);
        $response->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));
        $this->assertSame('Sabah', $shift->name);
        $this->assertSame($weekStart->toDateString(), $shift->start_date->toDateString());
        $this->assertSame(0, $shift->dayCouriers()->count());

        $index = $this->actingAs($user)->get(route('shift-planning.index', [
            'business_id' => $business->id,
            'week' => $weekStart->toDateString(),
        ]));
        $index->assertOk();
        $index->assertSee('Sabah');
        $index->assertSee('Kurye yok');
    }

    public function test_can_assign_different_couriers_per_day(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courierA = $this->createCourier($user, ['full_name' => 'Ayşe Demir']);
        $courierB = $this->createCourier($user, ['full_name' => 'Mehmet Kaya']);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $monday = $weekStart->toDateString();
        $tuesday = $weekStart->copy()->addDay()->toDateString();

        foreach ([$courierA, $courierB] as $courier) {
            BusinessCourierAssignment::factory()->create([
                'business_id' => $business->id,
                'courier_id' => $courier->id,
                'status' => 'active',
                'start_date' => '2026-01-01',
                'end_date' => null,
                'assigned_by' => $user->id,
            ]);
        }

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Gündüz',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('shift-planning.assign-couriers', $shift->id), [
            'work_date' => $monday,
            'courier_ids' => [$courierA->id],
            'week' => $weekStart->toDateString(),
        ])->assertRedirect();

        $this->actingAs($user)->put(route('shift-planning.assign-couriers', $shift->id), [
            'work_date' => $tuesday,
            'courier_ids' => [$courierB->id],
            'week' => $weekStart->toDateString(),
        ])->assertRedirect();

        $this->assertTrue(
            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $monday)
                ->where('courier_id', $courierA->id)
                ->exists()
        );
        $this->assertTrue(
            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $tuesday)
                ->where('courier_id', $courierB->id)
                ->exists()
        );
        $this->assertFalse(
            BusinessShiftDayCourier::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', $monday)
                ->where('courier_id', $courierB->id)
                ->exists()
        );

        $index = $this->actingAs($user)->get(route('shift-planning.index', [
            'business_id' => $business->id,
            'week' => $weekStart->toDateString(),
        ]));
        $index->assertSee('Ayşe Demir');
        $index->assertSee('Mehmet Kaya');
    }

    public function test_cannot_assign_courier_outside_shift_date_range(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'assigned_by' => $user->id,
        ]);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Kısa',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('shift-planning.assign-couriers', $shift->id), [
            'work_date' => '2026-07-20',
            'courier_ids' => [$courier->id],
        ]);

        $response->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('business_shift_day_couriers', 0);
    }

    public function test_cannot_assign_courier_not_linked_to_business(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '16:00',
            'end_time' => '00:00',
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('shift-planning.assign-couriers', $shift->id), [
            'work_date' => $weekStart->toDateString(),
            'courier_ids' => [$courier->id],
        ]);

        $response->assertSessionHasErrors('courier_ids.0');
        $this->assertDatabaseCount('business_shift_day_couriers', 0);
    }

    public function test_can_delete_only_selected_day_or_entire_shift(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-10',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('shift-planning.destroy', $shift->id), [
            'scope' => 'day',
            'work_date' => '2026-07-07',
        ])->assertRedirect();

        $this->assertNotSoftDeleted('business_shifts', ['id' => $shift->id]);
        $this->assertContains('2026-07-07', $shift->fresh()->excluded_dates);
        $this->assertFalse($shift->fresh()->runsOnDate('2026-07-07'));
        $this->assertTrue($shift->fresh()->runsOnDate('2026-07-08'));

        $this->actingAs($user)->delete(route('shift-planning.destroy', $shift->id), [
            'scope' => 'all',
            'work_date' => '2026-07-08',
        ])->assertRedirect();

        $this->assertSoftDeleted('business_shifts', ['id' => $shift->id]);
    }

    public function test_can_update_and_delete_shift(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğle',
            'start_time' => '12:00',
            'end_time' => '18:00',
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $update = $this->actingAs($user)->put(route('shift-planning.update', $shift->id), [
            'name' => 'Öğleden Sonra',
            'start_time' => '13:00',
            'end_time' => '21:00',
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekStart->copy()->addDays(13)->toDateString(),
            'days_of_week' => [1, 3, 5],
            'is_active' => '1',
        ]);

        $update->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));
        $this->assertSame('Öğleden Sonra', $shift->fresh()->name);
        $this->assertSame([1, 3, 5], $shift->fresh()->days_of_week);

        $delete = $this->actingAs($user)->delete(route('shift-planning.destroy', $shift->id), [
            'scope' => 'all',
        ]);
        $delete->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));
        $this->assertSoftDeleted('business_shifts', ['id' => $shift->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBusiness(User $user, array $overrides = []): Business
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Business::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCourier(User $user, array $overrides = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'created_by' => $user->id,
        ], $overrides));
    }
}
