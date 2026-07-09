<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContractTest extends TestCase
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

    public function test_contracts_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.contracts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_contracts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'contract_number' => 'SZL-2026-001',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.contracts.index'));

        $response->assertOk();
        $response->assertSee('Sözleşmeler');
        $response->assertSee('SZL-2026-001');
        $response->assertSee('Yeni Sözleşme');
        $response->assertSee($business->company_name);
    }

    public function test_authenticated_user_can_view_contract_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Burger House Gıda Ltd. Şti.',
        ]);

        $contract = Contract::factory()->create([
            'contractable_type' => Business::class,
            'contractable_id' => $business->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('businesses.contracts.show', $contract->id));

        $response->assertOk();
        $response->assertSee('Sözleşme Bilgileri');
        $response->assertSee('Burger House');
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
