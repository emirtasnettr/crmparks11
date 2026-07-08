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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessUpdateTest extends TestCase
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

    public function test_business_update_requires_permission(): void
    {
        $user = User::factory()->create();
        $business = $this->createBusiness($user);

        $response = $this->actingAs($user)->put(route('businesses.update', $business->id), [
            'company_name' => 'Test İşletme',
            'phone' => '0212 000 00 00',
            'pricing_model' => 'per_package',
            'earning_period' => 'weekly',
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }

    public function test_business_can_be_updated_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $business = $this->createBusiness($user);

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->put(route('businesses.update', $business->id), [
            'company_name' => 'Güncel Burger House',
            'brand_name' => 'Burger House',
            'phone' => '0216 555 12 34',
            'email' => 'info@burgerhouse.test',
            'website' => 'https://burgerhouse.test',
            'tax_office' => 'Kadıköy',
            'tax_number' => $business->tax_number,
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'address' => 'Test adres',
            'pricing_model' => 'per_package',
            'customer_price' => '50.00',
            'courier_price' => '35.00',
            'earning_period' => 'weekly',
            'status' => 'active',
            'notes' => 'Güncellenmiş not',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));
        $response->assertSessionHas('success', 'İşletme bilgileri güncellendi.');

        $business->refresh();

        $this->assertSame('Güncel Burger House', $business->company_name);
        $this->assertSame('Güncellenmiş not', $business->notes);
        $this->assertNotEmpty($business->logo_path);

        Storage::disk('public')->assertExists($business->logo_path);

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));

        $showResponse->assertOk();
        $showResponse->assertSee('Güncel Burger House');
        $showResponse->assertSee('50,00 ₺', false);
        $showResponse->assertSee('35,00 ₺', false);

        $stats = \App\Modules\Business\Data\BusinessOverviewStats::forBusiness(
            $business->id,
            \Carbon\Carbon::parse('2026-07-02'),
            \Carbon\Carbon::parse('2026-07-08'),
        );

        $this->assertSame(50.0, $stats['received_per_package']);
        $this->assertSame(35.0, $stats['courier_per_package']);
        $this->assertSame(15.0, $stats['net_per_package']);
    }

    public function test_business_status_change_reflects_on_show_and_index(): void
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
            'status' => 'pending',
            'tax_number' => $business->tax_number,
        ]);

        $response->assertRedirect(route('businesses.show', $business->id));

        $business->refresh();
        $this->assertSame('pending', $business->status);

        $showResponse = $this->actingAs($user)->get(route('businesses.show', $business->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Beklemede');

        $indexResponse = $this->actingAs($user)->get(route('businesses.index', ['status' => 'pending']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tatlı Diyarı');
        $indexResponse->assertSee('Beklemede');
    }

    public function test_business_update_returns_404_for_unknown_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->put(route('businesses.update', 999), [
            'company_name' => 'Test',
            'phone' => '0212 000 00 00',
            'pricing_model' => 'per_package',
            'earning_period' => 'weekly',
            'status' => 'active',
        ]);

        $response->assertNotFound();
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
