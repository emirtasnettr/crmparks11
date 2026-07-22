<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessIndexTest extends TestCase
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

    public function test_business_index_requires_authentication(): void
    {
        $response = $this->get(route('businesses.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_business_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->createBusiness($user, [
            'company_name' => 'Burger House Gıda Ltd. Şti.',
            'brand_name' => 'Burger House',
            'status' => 'contract_stage',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.index'));

        $response->assertOk();
        $response->assertSee('İşletmeler');
        $response->assertSee('Burger House');
        $response->assertSee('Yeni İşletme');
        $response->assertDontSee('>Logo<', false);
        $response->assertSee('İşletmeden Alınan Ücret');
        $response->assertSee('Kuryeye Verilen Ücret');
        $response->assertSee('Kontrat Tipi');
        $response->assertSee('45,00 ₺', false);
        $response->assertSee('32,00 ₺', false);
        $response->assertSee('Sözleşme Aşamasında');
    }

    public function test_business_contract_stage_status_reflects_on_show_and_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
            'brand_name' => 'Tatlı Diyarı',
            'phone' => '0224 666 77 88',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('businesses.update', $business->id), [
            'company_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
            'brand_name' => 'Tatlı Diyarı',
            'phone' => '0224 666 77 88',
            'city' => 'Bursa',
            'district' => 'Nilüfer',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 4,
            'latitude' => 41.0082,
            'longitude' => 28.9784,
            'status' => 'contract_stage',
            'estimated_opening_date' => '2026-09-01',
            'tax_number' => $business->tax_number,
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Sözleşme Aşamasında');
        $showResponse->assertSee('01.09.2026');

        $indexResponse = $this->actingAs($user)->get(route('businesses.index', ['status' => 'contract_stage']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tatlı Diyarı');
        $indexResponse->assertSee('Sözleşme Aşamasında');
    }

    public function test_business_opening_stage_status_is_available(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user, [
            'company_name' => 'Yeni Şube Gıda Ltd. Şti.',
            'brand_name' => 'Yeni Şube',
            'phone' => '0212 111 22 33',
            'status' => 'active',
        ]);

        $createResponse = $this->actingAs($user)->get(route('businesses.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Açılış Aşamasında');

        $response = $this->actingAs($user)->put(route('businesses.update', $business->id), [
            'company_name' => 'Yeni Şube Gıda Ltd. Şti.',
            'brand_name' => 'Yeni Şube',
            'phone' => '0212 111 22 33',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'earning_period' => 'weekly',
            'first_invoice_date' => '2026-07-14',
            'planned_courier_count' => 5,
            'latitude' => 41.0082,
            'longitude' => 28.9784,
            'status' => 'opening_stage',
            'start_date' => '2026-07-20',
            'tax_number' => $business->tax_number,
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));
        $this->assertSame('opening_stage', $business->fresh()->status);
        $this->assertSame('2026-07-20', $business->fresh()->start_date?->toDateString());

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Açılış Aşamasında');
        $showResponse->assertSee('20.07.2026');

        $indexResponse = $this->actingAs($user)->get(route('businesses.index', ['status' => 'opening_stage']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Yeni Şube');
        $indexResponse->assertSee('Açılış Aşamasında');
    }

    public function test_authenticated_user_can_view_business_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.create'));

        $response->assertOk();
        $response->assertSee('Yeni İşletme');
        $response->assertSee('Genel Bilgiler');
        $response->assertDontSee('Çalışma Modeli');
        $response->assertSee('Fatura Periyodu');
        $response->assertSee('İlk Fatura Tarihi');
        $response->assertSee(\App\Modules\Business\Data\BusinessFormData::defaultFirstInvoiceDate());
        $response->assertDontSee('Garanti Paket Sayısı');
        $response->assertSee('Kaydet');
        $response->assertSee(route('businesses.store'), false);
    }

    public function test_business_index_lists_active_businesses_first_then_alphabetically(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->createBusiness($user, [
            'brand_name' => 'Zebra Pasif',
            'company_name' => 'Zebra Pasif Ltd.',
            'status' => 'inactive',
        ]);
        $this->createBusiness($user, [
            'brand_name' => 'Beta Aktif',
            'company_name' => 'Beta Aktif Ltd.',
            'status' => 'active',
        ]);
        $this->createBusiness($user, [
            'brand_name' => 'Alpha Aktif',
            'company_name' => 'Alpha Aktif Ltd.',
            'status' => 'active',
        ]);
        $this->createBusiness($user, [
            'brand_name' => 'Ada Pasif',
            'company_name' => 'Ada Pasif Ltd.',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.index'));
        $response->assertOk();

        $content = $response->getContent();
        $alpha = strpos($content, 'Alpha Aktif');
        $beta = strpos($content, 'Beta Aktif');
        $ada = strpos($content, 'Ada Pasif');
        $zebra = strpos($content, 'Zebra Pasif');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertNotFalse($ada);
        $this->assertNotFalse($zebra);
        $this->assertTrue($alpha < $beta, 'Active businesses should be alphabetical');
        $this->assertTrue($beta < $ada, 'Active businesses should appear before inactive');
        $this->assertTrue($ada < $zebra, 'Inactive businesses should be alphabetical');
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
