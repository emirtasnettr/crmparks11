<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\ShiftPlanning\Models\BusinessShiftJokerAssignment;
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

        $this->assignCourier($business, $courier, $user);

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
        $this->assertDatabaseHas('business_shift_couriers', [
            'business_shift_id' => $shift->id,
            'courier_id' => $courier->id,
        ]);
    }

    public function test_roster_cannot_exceed_headcount(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $business = $this->createBusiness($user);
        $c1 = $this->createCourier($user, ['full_name' => 'Kurye 1']);
        $c2 = $this->createCourier($user, ['full_name' => 'Kurye 2']);
        $this->assignCourier($business, $c1, $user);
        $this->assignCourier($business, $c2, $user);

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

    public function test_can_assign_joker_for_absent_roster_courier(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $roster = $this->createCourier($user, ['full_name' => 'Kadrolu Kurye']);
        $joker = $this->createCourier($user, ['full_name' => 'Joker Kurye']);
        $this->assignCourier($business, $roster, $user);
        $this->assignCourier($business, $joker, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Öğlen',
            'start_time' => '12:00',
            'end_time' => '20:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $roster->id,
        ]);

        $this->actingAs($user)
            ->post(route('shift-planning.jokers.store', $shift->id), [
                'work_date' => '2026-07-20',
                'absent_courier_id' => $roster->id,
                'joker_courier_id' => $joker->id,
                'reason' => 'hasta',
                'notes' => 'Ateş',
            ])
            ->assertRedirect(route('shift-planning.index', ['business_id' => $business->id]));

        $this->assertTrue(
            BusinessShiftJokerAssignment::query()
                ->where('business_shift_id', $shift->id)
                ->whereDate('work_date', '2026-07-20')
                ->where('absent_courier_id', $roster->id)
                ->where('joker_courier_id', $joker->id)
                ->where('reason', 'hasta')
                ->exists()
        );

        $this->actingAs($user)
            ->get(route('shift-planning.index', [
                'business_id' => $business->id,
                'week' => '2026-07-20',
            ]))
            ->assertOk()
            ->assertSee('Joker Kurye')
            ->assertSee('Kadrolu Kurye');
    }

    public function test_joker_cannot_be_existing_roster_member(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $c1 = $this->createCourier($user, ['full_name' => 'A']);
        $c2 = $this->createCourier($user, ['full_name' => 'B']);
        $this->assignCourier($business, $c1, $user);
        $this->assignCourier($business, $c2, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'required_headcount' => 2,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->insert([
            ['business_shift_id' => $shift->id, 'courier_id' => $c1->id, 'created_at' => now(), 'updated_at' => now()],
            ['business_shift_id' => $shift->id, 'courier_id' => $c2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user)
            ->from(route('shift-planning.index', ['business_id' => $business->id]))
            ->post(route('shift-planning.jokers.store', $shift->id), [
                'work_date' => '2026-07-21',
                'absent_courier_id' => $c1->id,
                'joker_courier_id' => $c2->id,
                'reason' => 'izin',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('joker_courier_id');
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

    public function test_can_remove_joker_assignment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $roster = $this->createCourier($user, ['full_name' => 'Kadrolu']);
        $joker = $this->createCourier($user, ['full_name' => 'Joker']);
        $this->assignCourier($business, $roster, $user);
        $this->assignCourier($business, $joker, $user);

        $shift = BusinessShift::query()->create([
            'business_id' => $business->id,
            'name' => 'Sabah',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'required_headcount' => 1,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        BusinessShiftCourier::query()->create([
            'business_shift_id' => $shift->id,
            'courier_id' => $roster->id,
        ]);
        $assignment = BusinessShiftJokerAssignment::query()->create([
            'business_shift_id' => $shift->id,
            'work_date' => '2026-07-22',
            'absent_courier_id' => $roster->id,
            'joker_courier_id' => $joker->id,
            'reason' => 'izin',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('shift-planning.jokers.destroy', $assignment->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('business_shift_joker_assignments', ['id' => $assignment->id]);
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
