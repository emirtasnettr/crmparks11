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
        $indexResponse->assertSee('Point Kurye Market Ltd. Şti.');
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

        $response->assertRedirect(route('businesses.show', $business->id));

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Murat Kaya');
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
