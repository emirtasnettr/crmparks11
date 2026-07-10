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

class AgencyContactStoreTest extends TestCase
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

    public function test_agency_contact_store_requires_permission(): void
    {
        $user = User::factory()->create();
        $agency = $this->createAgency($user);

        $response = $this->actingAs($user)->post(route('agencies.contacts.store'), [
            'agency_id' => $agency->id,
            'first_name' => 'Serkan',
            'last_name' => 'Yılmaz',
            'title' => 'Firma Sahibi',
            'phone' => '0532 401 01 01',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('agency_contacts', 0);
    }

    public function test_agency_contact_can_be_created_from_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        $response = $this->actingAs($user)->post(route('agencies.contacts.store'), [
            'agency_id' => $agency->id,
            'first_name' => 'Deniz',
            'last_name' => 'Aksoy',
            'title' => 'Operasyon Müdürü',
            'phone' => '0533 401 01 02',
            'email' => 'deniz@agency.test',
            'is_default' => true,
            'status' => 'active',
        ]);

        $contact = AgencyContact::query()->first();

        $this->assertNotNull($contact);
        $response->assertRedirect(route('agencies.contacts.index', ['agency_id' => $agency->id]));
        $response->assertSessionHas('success', 'Yetkili başarıyla eklendi.');
        $this->assertSame('Deniz Aksoy', $contact->full_name);
        $this->assertTrue($contact->is_default);

        $indexResponse = $this->actingAs($user)->get(route('agencies.contacts.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Deniz Aksoy');
    }

    public function test_agency_contact_can_be_created_from_agency_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $agency = $this->createAgency($user);

        $response = $this->actingAs($user)->post(route('agencies.contacts.store'), [
            'agency_id' => $agency->id,
            'first_name' => 'Serkan',
            'last_name' => 'Yılmaz',
            'title' => 'Firma Sahibi',
            'phone' => '0532 401 01 01',
            'redirect_to_agency' => true,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('agencies.show', $agency->id).'?tab=contacts');

        $showResponse = $this->actingAs($user)->get(route('agencies.show', $agency->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Serkan Yılmaz');
    }

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
