<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyContactDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyContactIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_contacts_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.contacts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_contacts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.contacts.index'));

        $response->assertOk();
        $response->assertSee('Yetkililer');
        $response->assertSee('Acentelere ait tüm yetkilileri buradan yönetin.');
        $response->assertSee('Yeni Yetkili');
        $response->assertSee('Toplam Yetkili');
        $response->assertSee('Varsayılan Yetkili');
        $response->assertSee('Serkan Yılmaz');
        $response->assertSee('Operasyon Müdürü');
        $response->assertSee('⭐');
    }

    public function test_agency_contacts_have_at_least_twenty_five_records(): void
    {
        $contacts = AgencyContactDummyData::all();

        $this->assertCount(28, $contacts);
        $this->assertGreaterThanOrEqual(25, count($contacts));
    }

    public function test_each_agency_has_only_one_default_contact_in_dummy_data(): void
    {
        $defaultsByAgency = collect(AgencyContactDummyData::all())
            ->where('is_default', true)
            ->groupBy('agency_id')
            ->map->count();

        foreach ($defaultsByAgency as $agencyId => $count) {
            $this->assertEquals(1, $count, "Agency {$agencyId} should have exactly one default contact.");
        }
    }

    public function test_summary_stats_are_calculated(): void
    {
        $summary = AgencyContactDummyData::summarize();

        $this->assertEquals(28, $summary['total']);
        $this->assertGreaterThan(0, $summary['active']);
        $this->assertGreaterThan(0, $summary['default']);
    }

    public function test_agency_contacts_can_be_filtered_by_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.contacts.index', [
            'agency_id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Serkan Yılmaz');
        $response->assertSee('Deniz Aksoy');
        $response->assertDontSee('Ayşe Korkmaz');
    }

    public function test_authenticated_user_can_view_agency_contact_show_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.contacts.show', 1));

        $response->assertOk();
        $response->assertSee('Serkan Yılmaz');
        $response->assertSee('Yetkili Bilgileri');
        $response->assertSee('Bağlı Acente');
        $response->assertSee('İletişim Bilgileri');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
        $response->assertSee('Ana iletişim noktası.');
    }
}
