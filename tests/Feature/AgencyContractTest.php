<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyContractTest extends TestCase
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

    public function test_agency_contracts_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.contracts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_contracts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        Contract::factory()->create([
            'contractable_type' => Agency::class,
            'contractable_id' => $agency->id,
            'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
            'contract_number' => 'ACS-2026-001',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.contracts.index'));

        $response->assertOk();
        $response->assertSee('Sözleşmeler');
        $response->assertSee('Yeni Sözleşme');
        $response->assertSee('ACS-2026-001');
        $response->assertSee($agency->displayName());
    }

    public function test_authenticated_user_can_view_agency_contract_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user, [
            'company_name' => 'Metro Lojistik Acente A.Ş.',
        ]);

        $contract = Contract::factory()->create([
            'contractable_type' => Agency::class,
            'contractable_id' => $agency->id,
            'contract_type_id' => ContractType::query()->where('code', 'framework')->value('id'),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.contracts.show', $contract->id));

        $response->assertOk();
        $response->assertSee('Sözleşme Bilgileri');
        $response->assertSee($agency->displayName());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgency(User $user, array $overrides = []): Agency
    {
        $city = City::query()->where('name', 'İstanbul')->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', 'Kadıköy')
            ->firstOrFail();

        return Agency::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
