<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessPricing;
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
            'pricing_model' => 'daily',
            'earning_period' => 'weekly',
            'status' => 'contract_stage',
            'tax_number' => $business->tax_number,
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Sözleşme Aşamasında');

        $indexResponse = $this->actingAs($user)->get(route('businesses.index', ['status' => 'contract_stage']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tatlı Diyarı');
        $indexResponse->assertSee('Sözleşme Aşamasında');
    }

    public function test_authenticated_user_can_view_business_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get(route('businesses.create'));

        $response->assertOk();
        $response->assertSee('Yeni İşletme');
        $response->assertSee('Genel Bilgiler');
        $response->assertSee('Çalışma Modeli');
        $response->assertSee('Hakediş Periyodu');
        $response->assertSee('Kaydet');
        $response->assertSee(route('businesses.store'), false);
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

        $business = Business::factory()->create(array_merge([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'created_by' => $user->id,
        ], $overrides));

        $pricingModel = PricingModelType::query()->where('code', 'per_package')->firstOrFail();
        $business->pricings()->delete();
        BusinessPricing::query()->create([
            'business_id' => $business->id,
            'pricing_model_type_id' => $pricingModel->id,
            'customer_unit_price' => 45,
            'courier_unit_price' => 32,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return $business;
    }
}
