<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyIndexTest extends TestCase
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

    public function test_agencies_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agencies_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createAgency($user, [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
            'status' => 'active',
        ]);
        $this->createAgency($user, [
            'company_name' => 'Metro Lojistik Acente A.Ş.',
            'status' => 'active',
        ]);
        $this->createAgency($user, [
            'company_name' => 'Express Dağıtım Acentesi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.index'));

        $response->assertOk();
        $response->assertSee('Acenteler');
        $response->assertSee('Sistemde kayıtlı tüm acenteleri buradan yönetin.');
        $response->assertSee('Yeni Acente');
        $response->assertSee('Toplam Acente');
        $response->assertSee('Aktif Acente');
        $response->assertDontSee('Bu Ay Toplam Hakediş');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
        $response->assertSee('Metro Lojistik Acente A.Ş.');
        $response->assertSee('Express Dağıtım Acentesi');
        $response->assertSee('Beklemede');
    }

    public function test_summary_stats_are_calculated(): void
    {
        $user = User::factory()->create();
        $this->createAgency($user, ['status' => 'active']);
        $this->createAgency($user, ['status' => 'active']);
        $this->createAgency($user, ['status' => 'inactive']);

        $summary = app(\App\Modules\Agency\Services\AgencyService::class)->summary([]);

        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(2, $summary['active']);
    }

    public function test_agencies_can_be_filtered_by_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createAgency($user, [
            'company_name' => 'Anadolu Kurye Hizmetleri Ltd. Şti.',
            'city' => 'Ankara',
            'district' => 'Çankaya',
        ]);
        $this->createAgency($user, [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.index', [
            'city' => 'Ankara',
        ]));

        $response->assertOk();
        $response->assertSee('Anadolu Kurye Hizmetleri Ltd. Şti.');
        $response->assertDontSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_agencies_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createAgency($user, [
            'company_name' => 'Konya Merkez Lojistik',
            'status' => 'inactive',
        ]);
        $this->createAgency($user, [
            'company_name' => 'Anadolu Kurye Hizmetleri Ltd. Şti.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee('Konya Merkez Lojistik');
        $response->assertDontSee('Anadolu Kurye Hizmetleri Ltd. Şti.');
    }

    public function test_agencies_can_be_filtered_by_courier_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createAgency($user, [
            'company_name' => 'Konya Merkez Lojistik',
            'status' => 'inactive',
        ]);
        $agencyWithCouriers = $this->createAgency($user, [
            'company_name' => 'Aydın Ege Dağıtım Acentesi',
            'status' => 'active',
        ]);
        Courier::factory()->count(3)->create([
            'agency_id' => $agencyWithCouriers->id,
            'courier_type' => 'agency',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.index', [
            'courier_count' => '0',
        ]));

        $response->assertOk();
        $response->assertSee('Konya Merkez Lojistik');
        $response->assertDontSee('Aydın Ege Dağıtım Acentesi');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgency(User $user, array $overrides = []): Agency
    {
        $cityName = $overrides['city'] ?? 'İstanbul';
        $districtName = $overrides['district'] ?? 'Kadıköy';
        unset($overrides['city'], $overrides['district']);

        $city = City::query()->where('name', $cityName)->firstOrFail();
        $district = District::query()
            ->where('city_id', $city->id)
            ->where('name', $districtName)
            ->firstOrFail();

        return Agency::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));
    }
}
