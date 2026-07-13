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

class BusinessAssignmentTest extends TestCase
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

    public function test_assignments_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.assignments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_assignments_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
            'phone' => '0532 100 10 01',
        ]);

        BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.assignments.index'));

        $response->assertOk();
        $response->assertSee('Atanan Kuryeler');
        $response->assertSee('Ahmet Yıldız');
        $response->assertSee('Yeni Kurye Ataması');
        $response->assertSee('Aktif Atama');
        $response->assertSee($business->displayName());
    }

    public function test_authenticated_user_can_view_assignment_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);
        $courier = $this->createCourier($user, [
            'full_name' => 'Ahmet Yıldız',
            'phone' => '0532 100 10 01',
        ]);

        $assignment = BusinessCourierAssignment::factory()->create([
            'business_id' => $business->id,
            'courier_id' => $courier->id,
            'assigned_by' => $user->id,
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.assignments.show', $assignment->id));

        $response->assertOk();
        $response->assertSee('Kurye Bilgileri');
        $response->assertSee('Atama Tarihleri');
        $response->assertSee('Ahmet Yıldız');
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
