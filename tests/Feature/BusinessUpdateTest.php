<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Business\Services\BusinessProfileStore;
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

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_business_update_requires_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('businesses.update', 1), [
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

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user)->put(route('businesses.update', 1), [
            'company_name' => 'Güncel Burger House',
            'brand_name' => 'Burger House',
            'phone' => '0216 555 12 34',
            'email' => 'info@burgerhouse.test',
            'website' => 'https://burgerhouse.test',
            'tax_office' => 'Kadıköy',
            'tax_number' => '1234567890',
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

        $response->assertRedirect(route('businesses.show', 1));
        $response->assertSessionHas('success', 'İşletme bilgileri güncellendi.');

        $stored = BusinessProfileStore::get(1);

        $this->assertSame('Güncel Burger House', $stored['company_name']);
        $this->assertSame('Güncellenmiş not', $stored['notes']);
        $this->assertNotEmpty($stored['logo_path']);
        $this->assertNotEmpty($stored['logo_url']);

        Storage::disk('public')->assertExists($stored['logo_path']);

        $showResponse = $this->actingAs($user)->get(route('businesses.show', 1));

        $showResponse->assertOk();
        $showResponse->assertSee('Güncel Burger House');
        $showResponse->assertSee($stored['logo_url'], false);
        $showResponse->assertSee('50,00 ₺', false);
        $showResponse->assertSee('35,00 ₺', false);

        $stats = \App\Modules\Business\Data\BusinessOverviewStats::forBusiness(
            1,
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

        $response = $this->actingAs($user)->put(route('businesses.update', 6), [
            'company_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
            'brand_name' => 'Tatlı Diyarı',
            'phone' => '0224 666 77 88',
            'city' => 'Bursa',
            'district' => 'Nilüfer',
            'pricing_model' => 'daily',
            'earning_period' => 'weekly',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('businesses.show', 6));

        $stored = BusinessProfileStore::get(6);
        $this->assertSame('pending', $stored['status']);

        $showResponse = $this->actingAs($user)->get(route('businesses.show', 6));
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
}
