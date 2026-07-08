<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

    public function test_agency_dummy_data_has_at_least_twenty_records(): void
    {
        $agencies = AgencyDummyData::all();

        $this->assertCount(25, $agencies);
        $this->assertGreaterThanOrEqual(20, count($agencies));
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyDummyData::summary();

        $this->assertEquals(25, $summary['total']);
        $this->assertGreaterThan(0, $summary['active']);
        $this->assertGreaterThan(0, $summary['total_couriers']);
        $this->assertGreaterThan(0, $summary['monthly_earnings']);
    }

    public function test_agencies_can_be_filtered_by_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

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

        $response = $this->actingAs($user)->get(route('agencies.index', [
            'courier_count' => '0',
        ]));

        $response->assertOk();
        $response->assertSee('Konya Merkez Lojistik');
        $response->assertSee('Balıkesir Kurye Hizmetleri Ltd. Şti.');
        $response->assertDontSee('Aydın Ege Dağıtım Acentesi');
    }
}
