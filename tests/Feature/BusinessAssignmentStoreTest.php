<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessCourierAssignment;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAssignmentStoreTest extends TestCase
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

    public function test_business_assignment_store_requires_permission(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user);

        $response = $this->actingAs($user)->post(route('businesses.assignments.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('business_courier_assignments', 0);
    }

    public function test_business_assignment_can_be_created_from_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Point Kurye Market Ltd. Şti.',
        ]);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
            'phone' => '0532 100 10 01',
        ]);

        $response = $this->actingAs($user)->post(route('businesses.assignments.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'notes' => 'Test ataması',
            'status' => 'active',
        ]);

        $assignment = BusinessCourierAssignment::query()->first();

        $this->assertNotNull($assignment);
        $response->assertRedirect(route('businesses.assignments.index', ['business_id' => $business->id]));
        $response->assertSessionHas('success', 'Kurye ataması başarıyla oluşturuldu.');

        $this->assertSame($business->id, $assignment->business_id);
        $this->assertSame($courier->id, $assignment->courier_id);
        $this->assertSame($user->id, $assignment->assigned_by);

        $indexResponse = $this->actingAs($user)->get(route('businesses.assignments.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Ahmet Yıldız');
        $indexResponse->assertSee($business->displayName());
    }

    public function test_operations_specialist_can_create_assignment_and_open_businesses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operations_specialist');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ops Atama Kurye',
        ]);

        $this->actingAs($user)->get(route('businesses.index'))->assertOk();

        $response = $this->actingAs($user)->post(route('businesses.assignments.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.assignments.index', ['business_id' => $business->id]));
        $this->assertDatabaseCount('business_courier_assignments', 1);
    }

    public function test_business_assignment_can_be_created_from_business_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Murat Kaya',
        ]);

        $response = $this->actingAs($user)->post(route('businesses.assignments.store'), [
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-02-01',
            'redirect_to_business' => true,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.show', $business->id).'?tab=assignments');

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Murat Kaya');
    }

    public function test_courier_cannot_have_two_active_business_assignments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'İşletme A']);
        $businessB = $this->createBusiness($user, ['brand_name' => 'İşletme B']);
        $courier = $this->createCourier($user, ['full_name' => 'Tek İşletme Kurye']);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $businessA->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'status' => 'active',
            'assigned_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('businesses.assignments.index'))
            ->post(route('businesses.assignments.store'), [
                'business_id' => $businessB->id,
                'courier_id' => $courier->id,
                'start_date' => '2026-03-01',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('businesses.assignments.index'));
        $response->assertSessionHasErrors('courier_id');
        $this->assertDatabaseCount('business_courier_assignments', 1);
    }

    public function test_courier_can_be_reassigned_after_previous_assignment_ends(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $businessA = $this->createBusiness($user, ['brand_name' => 'Eski İşletme']);
        $businessB = $this->createBusiness($user, ['brand_name' => 'Yeni İşletme']);
        $courier = $this->createCourier($user, ['full_name' => 'Yeniden Atanan']);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $businessA->id,
            'courier_id' => $courier->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'inactive',
            'assigned_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('businesses.assignments.store'), [
            'business_id' => $businessB->id,
            'courier_id' => $courier->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.assignments.index', ['business_id' => $businessB->id]));
        $this->assertDatabaseCount('business_courier_assignments', 2);
        $this->assertDatabaseHas('business_courier_assignments', [
            'business_id' => $businessB->id,
            'courier_id' => $courier->id,
            'status' => 'active',
        ]);
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
