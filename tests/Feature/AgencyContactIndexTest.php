<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyContactIndexTest extends TestCase
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

    public function test_agency_contacts_index_requires_authentication(): void
    {
        $response = $this->get(route('agencies.contacts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_agency_contacts_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);
        AgencyContact::factory()->create([
            'agency_id' => $agency->id,
            'full_name' => 'Serkan Yılmaz',
            'title' => 'Operasyon Müdürü',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.contacts.index'));

        $response->assertOk();
        $response->assertSee('Yetkililer');
        $response->assertSee('Acentelere ait tüm yetkilileri buradan yönetin.');
        $response->assertSee('Yeni Yetkili');
        $response->assertSee('Toplam Yetkili');
        $response->assertSee('Serkan Yılmaz');
        $response->assertSee('Operasyon Müdürü');
        $response->assertSee('⭐');
    }

    public function test_agency_contacts_can_be_filtered_by_agency(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agencyA = $this->createAgency($user, ['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);
        $agencyB = $this->createAgency($user, ['company_name' => 'Metro Lojistik Ltd. Şti.']);

        AgencyContact::factory()->create([
            'agency_id' => $agencyA->id,
            'full_name' => 'Serkan Yılmaz',
        ]);
        AgencyContact::factory()->create([
            'agency_id' => $agencyA->id,
            'full_name' => 'Deniz Aksoy',
        ]);
        AgencyContact::factory()->create([
            'agency_id' => $agencyB->id,
            'full_name' => 'Ayşe Korkmaz',
        ]);

        $response = $this->actingAs($user)->get(route('agencies.contacts.index', [
            'agency_id' => $agencyA->id,
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
        $agency = $this->createAgency($user, [
            'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
        ]);
        $contact = AgencyContact::factory()->create([
            'agency_id' => $agency->id,
            'full_name' => 'Serkan Yılmaz',
            'title' => 'Firma Sahibi',
            'notes' => 'Ana iletişim noktası.',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('agencies.contacts.show', $contact->id));

        $response->assertOk();
        $response->assertSee('Serkan Yılmaz');
        $response->assertSee('Yetkili Bilgileri');
        $response->assertSee('Bağlı Acente');
        $response->assertSee('İletişim Bilgileri');
        $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
        $response->assertSee('Ana iletişim noktası.');
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
