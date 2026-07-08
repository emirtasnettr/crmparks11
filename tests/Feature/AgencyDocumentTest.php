<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyDocumentDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_agency_documents_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.documents.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_documents_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.documents.index'));

        $response->assertOk();
        $response->assertSee('Evraklar');
        $response->assertSee('Acentelere ait tüm evrakları buradan yönetin.');
        $response->assertSee('Evrak Yükle');
        $response->assertSee('Toplam Evrak');
        $response->assertSee('Süresi Yaklaşan');
        $response->assertSee('VL-1234567890');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    }

    public function test_agency_documents_have_at_least_forty_records(): void
    {
        $documents = AgencyDocumentDummyData::all();

        $this->assertCount(46, $documents);
        $this->assertGreaterThanOrEqual(40, count($documents));
    }

    public function test_document_status_is_computed_from_expiry_date(): void
    {
        $documents = AgencyDocumentDummyData::all();

        $expiring = collect($documents)->firstWhere('status', 'expiring_soon');
        $expired = collect($documents)->firstWhere('status', 'expired');
        $valid = collect($documents)->firstWhere('status', 'valid');

        $this->assertNotNull($expiring);
        $this->assertNotNull($expired);
        $this->assertNotNull($valid);
        $this->assertLessThanOrEqual(30, $expiring['days_remaining']);
        $this->assertLessThan(0, $expired['days_remaining']);
        $this->assertGreaterThan(30, $valid['days_remaining']);
    }

    public function test_soft_deleted_documents_are_excluded_by_default(): void
    {
        $all = AgencyDocumentDummyData::all();
        $withTrashed = AgencyDocumentDummyData::all(true);

        $this->assertCount(46, $all);
        $this->assertCount(47, $withTrashed);
        $this->assertNull(AgencyDocumentDummyData::find(51));
        $this->assertNotNull(AgencyDocumentDummyData::find(51, true));
    }

    public function test_version_history_is_available_on_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.documents.show', 5));

        $response->assertOk();
        $response->assertSee('Versiyon Geçmişi');
        $response->assertSee('metro-lojistik-ticaret-sicil-v1.pdf');
        $response->assertSee('Güncel');
    }

    public function test_agency_documents_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $count = count(AgencyDocumentDummyData::filter(['status' => 'expired']));

        $response = $this->actingAs($user)->get(route('agencies.documents.index', [
            'status' => 'expired',
        ]));

        $response->assertOk();
        $response->assertSee('1–'.$count.' / '.$count);
        $response->assertSee('Süresi Dolmuş');
    }

    public function test_authenticated_user_can_view_agency_document_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('agencies.documents.show', 1));

        $response->assertOk();
        $response->assertSee('Acente Bilgileri');
        $response->assertSee('Belge Bilgileri');
        $response->assertSee('Dosya Önizleme');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
        $response->assertSee('VL-1234567890');
    }
}
