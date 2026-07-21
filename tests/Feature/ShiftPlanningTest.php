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

    public function test_can_create_fixed_shift_with_roster(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Zeynep Ak']);

        $response = $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'required_headcount' => 2,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ]);

        $shift = BusinessShift::query()->first();
        $this->assertNotNull($shift);
        $response->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));
        $this->assertSame(2, $shift->required_headcount);
        $this->assertSame(now()->toDateString(), $shift->start_date?->toDateString());
        $this->assertSame(now()->toDateString(), $shift->end_date?->toDateString());
        $this->assertDatabaseHas('business_shift_couriers', [
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);
    }

    public function test_courier_can_join_shift_without_business_assignment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Emir Taş']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));

        $this->assertDatabaseHas('business_shift_couriers', [
            'courier_id' => $courier->id,
        ]);
    }

    public function test_courier_can_work_non_overlapping_shifts_at_different_businesses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'A Market']);
        $businessB = $this->createBusiness($user, ['brand_name' => 'B Market', 'company_name' => 'B Market A.Ş.']);
        $courier = $this->createCourier($user, ['full_name' => 'Emir Taş']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $businessA->id,
            'name' => 'Sabah A',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $businessB->id,
            'name' => 'Öğleden Sonra B',
            'start_time' => '13:00',
            'end_time' => '17:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(2, BusinessShiftCourier::query()->where('courier_id', $courier->id)->count());
    }

    public function test_courier_cannot_have_overlapping_shifts_at_different_businesses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'A Market']);
        $businessB = $this->createBusiness($user, ['brand_name' => 'B Market', 'company_name' => 'B Market A.Ş.']);
        $courier = $this->createCourier($user, ['full_name' => 'Emir Taş']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $businessA->id,
            'name' => 'Sabah A',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->actingAs($user)
            ->from(route('shift-planning.index', ['business_id' => $businessB->id]))
            ->post(route('shift-planning.store'), [
                'business_id' => $businessB->id,
                'name' => 'Sabah B',
                'start_time' => '09:00',
                'end_time' => '12:00',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-03',
                'required_headcount' => 1,
                'courier_ids' => [$courier->id],
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('courier_ids');

        $this->assertSame(1, BusinessShiftCourier::query()->where('courier_id', $courier->id)->count());
    }

    public function test_adjacent_shift_times_do_not_conflict(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'A Market']);
        $businessB = $this->createBusiness($user, ['brand_name' => 'B Market', 'company_name' => 'B Market A.Ş.']);
        $courier = $this->createCourier($user, ['full_name' => 'Emir Taş']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $businessA->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $businessB->id,
            'name' => 'Öğle',
            'start_time' => '12:00',
            'end_time' => '17:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(2, BusinessShiftCourier::query()->where('courier_id', $courier->id)->count());
    }

    public function test_eligible_couriers_endpoint_hides_overlapping_assignments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'A Market']);
        $busy = $this->createCourier($user, ['full_name' => 'Meşgul Kurye']);
        $free = $this->createCourier($user, ['full_name' => 'Boş Kurye']);

        $shift = BusinessShift::query()->create([
            'business_id' => $businessA->id,
            'name' => 'Sabah A',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $busy->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('shift-planning.eligible-couriers', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]));

        $response->assertOk();
        $ids = collect($response->json('couriers'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($free->id, $ids);
        $this->assertNotContains($busy->id, $ids);

        $afternoon = $this->actingAs($user)->getJson(route('shift-planning.eligible-couriers', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'start_time' => '13:00',
            'end_time' => '17:00',
        ]));

        $afternoonIds = collect($afternoon->json('couriers'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($busy->id, $afternoonIds);
        $this->assertContains($free->id, $afternoonIds);

        // Aynı vardiya hariç tutulunca mevcut kadro üyesi listede kalır.
        $self = $this->actingAs($user)->getJson(route('shift-planning.eligible-couriers', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'exclude_shift_id' => $shift->id,
        ]));

        $selfIds = collect($self->json('couriers'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($busy->id, $selfIds);
    }

    public function test_can_create_shift_with_custom_date_range(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Haftalık',
            'start_time' => '10:00',
            'end_time' => '18:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
            'required_headcount' => 1,
            'is_active' => '1',
        ])->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));

        $shift = BusinessShift::query()->first();
        $this->assertNotNull($shift);
        $this->assertSame('2026-08-01', $shift->start_date?->toDateString());
        $this->assertSame('2026-08-15', $shift->end_date?->toDateString());
    }

    public function test_shift_runs_only_inside_selected_date_range(): void
    {
        $shift = new BusinessShift([
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
        ]);

        $this->assertFalse($shift->runsOn('2026-07-31'));
        $this->assertTrue($shift->runsOn('2026-08-01'));
        $this->assertTrue($shift->runsOn('2026-08-02'));
        $this->assertTrue($shift->runsOn('2026-08-03'));
        $this->assertFalse($shift->runsOn('2026-08-04'));
    }

    public function test_shift_without_date_range_never_runs(): void
    {
        $shift = new BusinessShift([
            'start_date' => null,
            'end_date' => null,
        ]);

        $this->assertFalse($shift->runsOn('2026-08-01'));
    }

    public function test_week_calendar_only_lists_occurrences_inside_shift_date_range(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'UniqueRangeShiftXYZ',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'required_headcount' => 1,
            'is_active' => '1',
        ])->assertRedirect();

        // Hafta: 27 Tem–2 Ağu. Vardiya yalnız 1 Ağu'da; sidebar + 1 gün = 2.
        $response = $this->actingAs($user)->get(route('shift-planning.index', [
            'business_id' => $business->id,
            'week' => '2026-07-27',
        ]));

        $response->assertOk();
        // Sidebar list + Alpine config + tek takvim günü (bug olsaydı 7 gün × = çok daha fazla).
        $this->assertSame(3, substr_count($response->getContent(), 'UniqueRangeShiftXYZ'));

        Carbon::setTestNow();
    }

    public function test_retrospective_shift_auto_completes_past_roster_days(): void
    {
        Carbon::setTestNow('2026-07-21 15:00:00');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Geçmiş Kurye']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Geçmiş Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => '2026-07-18',
            'end_date' => '2026-07-20',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $shift = BusinessShift::query()->first();
        $this->assertNotNull($shift);

        foreach (['2026-07-18', '2026-07-19', '2026-07-20'] as $date) {
            $this->assertTrue(
                BusinessShiftAttendance::query()
                    ->where('business_shift_id', $shift->id)
                    ->where('courier_id', $courier->id)
                    ->whereDate('work_date', $date)
                    ->where('status', 'completed')
                    ->exists(),
                "Expected completed attendance on {$date}",
            );
        }

        $this->assertFalse(
            BusinessShiftAttendance::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', '2026-07-21')
                ->exists()
        );

        Carbon::setTestNow();
    }

    public function test_retrospective_shift_completes_today_only_after_shift_end(): void
    {
        Carbon::setTestNow('2026-07-21 18:30:00');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, ['full_name' => 'Bugün Kurye']);

        $this->actingAs($user)->post(route('shift-planning.store'), [
            'business_id' => $business->id,
            'name' => 'Bugün Biten',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'required_headcount' => 1,
            'courier_ids' => [$courier->id],
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertTrue(
            BusinessShiftAttendance::query()
                ->where('courier_id', $courier->id)
                ->whereDate('work_date', '2026-07-21')
                ->where('status', 'completed')
                ->exists()
        );

        Carbon::setTestNow();
    }

    public function test_roster_cannot_exceed_headcount(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $business = $this->createBusiness($user);
        $c1 = $this->createCourier($user, ['full_name' => 'Kurye 1']);
        $c2 = $this->createCourier($user, ['full_name' => 'Kurye 2']);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Akşam',
            'start_time' => '17:00',
            'end_time' => '01:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('shift-planning.index', ['business_id' => $business->id]))
            ->put(route('shift-planning.assign-couriers', $shift->id), [
                'courier_ids' => [$c1->id, $c2->id],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('courier_ids');

        $this->assertDatabaseCount('business_shift_couriers', 0);
    }

    public function test_can_delete_shift(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Gece',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('shift-planning.destroy', $shift->id))
            ->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));

        $this->assertSoftDeleted('business_shifts', ['id' => $shift->id]);
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
