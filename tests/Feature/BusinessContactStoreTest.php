<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessContactStoreTest extends TestCase
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

    public function test_business_contact_store_requires_permission(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->post(route('businesses.contacts.store'), [
            'business_id' => $business->id,
            'full_name' => 'Test Yetkili',
            'title' => 'İşletme Sahibi',
            'phone' => '0532 111 22 33',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('business_contacts', 0);
    }

    public function test_business_contact_can_be_created_from_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Point Kurye Market Ltd. Şti.',
        ]);

        $response = $this->actingAs($user)->post(route('businesses.contacts.store'), [
            'business_id' => $business->id,
            'full_name' => 'Ayşe Korkmaz',
            'title' => 'Operasyon Müdürü',
            'phone' => '0533 444 55 66',
            'email' => 'ayse@pointmarket.test',
            'is_default' => true,
            'status' => 'active',
        ]);

        $contact = BusinessContact::query()->first();

        $this->assertNotNull($contact);
        $response->assertRedirect(route('businesses.contacts.index', ['business_id' => $business->id]));
        $response->assertSessionHas('success', 'Yetkili başarıyla eklendi.');

        $this->assertSame('Ayşe Korkmaz', $contact->full_name);
        $this->assertTrue($contact->is_default);

        $indexResponse = $this->actingAs($user)->get(route('businesses.contacts.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Ayşe Korkmaz');
    }

    public function test_business_contact_can_be_created_from_business_show(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->post(route('businesses.contacts.store'), [
            'business_id' => $business->id,
            'full_name' => 'Mehmet Yılmaz',
            'title' => 'İşletme Sahibi',
            'phone' => '0532 100 10 01',
            'redirect_to_business' => true,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Mehmet Yılmaz');
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
