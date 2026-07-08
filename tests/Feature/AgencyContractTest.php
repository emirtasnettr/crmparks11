<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyContractDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

        $response = $this->actingAs($user)->get(route('agencies.contracts.index'));

        $response->assertOk();
        $response->assertSee('Sözleşmeler');
        $response->assertSee('Acenteler ile yapılan tüm sözleşmeleri buradan yönetin.');
        $response->assertSee('Yeni Sözleşme');
        $response->assertSee('Toplam Sözleşme');
        $response->assertSee('Yakında Bitecek');
        $response->assertSee('ACS-2026-001');
        $response->assertSee('Güncel Sözleşme');
        $response->assertSee('30 Gün İçinde Bitecek');
    }

    public function test_agency_contracts_have_at_least_twenty_five_records(): void
    {
        $contracts = AgencyContractDummyData::all();

        $this->assertCount(28, $contracts);
        $this->assertGreaterThanOrEqual(25, count($contracts));
    }

    public function test_each_agency_has_at_most_one_current_contract(): void
    {
        $currentByAgency = collect(AgencyContractDummyData::all())
            ->where('is_current', true)
            ->groupBy('agency_id')
            ->map->count();

        foreach ($currentByAgency as $agencyId => $count) {
            $this->assertEquals(1, $count, "Agency {$agencyId} should have exactly one current contract.");
        }
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyContractDummyData::summarize();

        $this->assertEquals(28, $summary['total']);
        $this->assertGreaterThan(0, $summary['active']);
        $this->assertGreaterThan(0, $summary['expiring_soon']);
        $this->assertGreaterThan(0, $summary['expired']);
    }

    public function test_agency_contracts_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $count = count(AgencyContractDummyData::filter(['status' => 'expiring_soon']));

        $response = $this->actingAs($user)->get(route('agencies.contracts.index', [
            'status' => 'expiring_soon',
        ]));

        $response->assertOk();
        $response->assertSee('1–'.$count.' / '.$count);
        $response->assertSee('ACS-2026-014');
    }

    public function test_authenticated_user_can_view_agency_contract_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.contracts.show', 3));

        $response->assertOk();
        $response->assertSee('Sözleşme Bilgileri');
        $response->assertSee('Acente Bilgileri');
        $response->assertSee('PDF Önizleme');
        $response->assertSee('Ek Belgeler');
        $response->assertSee('İşlem Geçmişi');
        $response->assertSee('Metro Lojistik Acente A.Ş.');
        $response->assertSee('13 gün içinde bitiyor.');
    }
}
