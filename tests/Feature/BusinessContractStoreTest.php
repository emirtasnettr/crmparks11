<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContractStoreTest extends TestCase
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

    public function test_business_contract_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->post(route('businesses.contracts.store'), [
            'business_id' => $business->id,
            'contract_number' => 'SZL-2026-100',
            'contract_type' => 'service',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.contracts.index', ['business_id' => $business->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contracts', [
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_number' => 'SZL-2026-100',
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
}
