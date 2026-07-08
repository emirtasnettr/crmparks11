<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\District;
use App\Models\PricingModelType;
use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessPricing;
use App\Modules\Courier\Data\CourierDummyData;
use App\Modules\Courier\Models\Courier;
use Database\Seeders\CitySeeder;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityShowPageTest extends TestCase
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

  public function test_business_show_accepts_custom_date_range(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $business = $this->createBusiness($user);

    $response = $this->actingAs($user)->get(route('businesses.show', [
      'id' => $business->id,
      'start_date' => '2026-07-01',
      'end_date' => '2026-07-05',
    ]));

    $response->assertOk();
    $response->assertSee('01.07.2026 – 05.07.2026', false);
    $response->assertSee('value="2026-07-01"', false);
    $response->assertSee('value="2026-07-05"', false);
  }

  public function test_business_overview_stats_include_per_package_metrics(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $business = $this->createBusiness($user);

    $stats = \App\Modules\Business\Data\BusinessOverviewStats::forBusiness(
      $business->id,
      \Carbon\Carbon::parse('2026-07-02'),
      \Carbon\Carbon::parse('2026-07-08'),
    );

    $this->assertGreaterThan(0, $stats['received_per_package']);
    $this->assertGreaterThan(0, $stats['courier_per_package']);
    $this->assertGreaterThan(0, $stats['active_couriers']);
    $this->assertSame(
      round($stats['received_per_package'] - $stats['courier_per_package'], 2),
      $stats['net_per_package']
    );
    $this->assertStringContainsString('KDV hariç', $stats['received_per_package_formatted']);

    $response = $this->actingAs($user)->get(route('businesses.show', $business->id));
    $response->assertSee(\App\Core\Helpers\MoneyCalculator::formatVatAmount($stats['received_per_package']), false);
    $response->assertSee('KDV hariç');
  }

  public function test_business_show_page_displays_profile_card(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $business = $this->createBusiness($user, [
      'company_name' => 'Burger House Gıda Ltd. Şti.',
      'brand_name' => 'Burger House',
    ]);
    \App\Modules\Business\Models\BusinessContact::factory()->create([
      'business_id' => $business->id,
      'full_name' => 'Mehmet Yılmaz',
      'title' => 'İşletme Sahibi',
    ]);

    $response = $this->actingAs($user)->get(route('businesses.show', $business->id));

    $response->assertOk();
    $response->assertSee('Burger House Gıda Ltd. Şti.');
    $response->assertSee('Paket Başı Alınan');
    $response->assertSee('Paket Başı Kuryeye Verilen');
    $response->assertSee('Paket Başı Net Kazanç');
    $response->assertSee('Operasyon Özeti');
    $response->assertSee('Yeni Yetkili');
    $response->assertSee('Yeni Sözleşme');
    $response->assertSee('Evrak Yükle');
    $response->assertSee('Yetkililer');
    $response->assertSee('Mehmet Yılmaz');
    $response->assertSee('SZL-2026-001');
    $response->assertDontSee('Hakedişler');
    $response->assertDontSee('Hakediş Periyodu');
  }

  public function test_courier_show_page_displays_profile_card(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $courier = $this->createCourier($user, [
      'first_name' => 'Ahmet',
      'last_name' => 'Yıldız',
      'full_name' => 'Ahmet Yıldız',
      'phone' => '0532 100 10 01',
    ]);

    $response = $this->actingAs($user)->get(route('couriers.show', $courier->id));

    $response->assertOk();
    $response->assertSee('Ahmet Yıldız');
    $response->assertSee('Belge Yükle');
    $response->assertSee('Yeni Araç');
    $response->assertSee('Belgeler');
    $response->assertSee('0532 100 10 01');
    $response->assertDontSee('Hakedişler');
  }

  public function test_agency_show_page_displays_profile_card(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $agency = $this->createAgency($user, [
      'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
      'authorized_person' => 'Serkan Yılmaz',
    ]);

    $response = $this->actingAs($user)->get(route('agencies.show', $agency->id));

    $response->assertOk();
    $response->assertSee('Hızlı Kurye Acentesi Ltd. Şti.');
    $response->assertSee('Kurye Ata');
    $response->assertSee('Yeni Sözleşme');
    $response->assertSee('Kuryeler');
    $response->assertSee('Serkan Yılmaz');
    $response->assertDontSee('Hakedişler');
    $response->assertDontSee('Aylık Hakediş');
  }

  public function test_submodule_routes_still_work_after_show_route(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get(route('businesses.contacts.index'))->assertOk();
    $this->actingAs($user)->get(route('couriers.documents.index'))->assertOk();
    $this->actingAs($user)->get(route('agencies.contacts.index'))->assertOk();
  }

  public function test_unknown_entity_returns_404(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get(route('businesses.show', 999))->assertNotFound();
    $this->actingAs($user)->get(route('couriers.show', 999))->assertNotFound();
    $this->actingAs($user)->get(route('agencies.show', 999))->assertNotFound();
  }

  public function test_courier_show_payload_only_includes_own_related_data(): void
  {
    $courier = CourierDummyData::showPayload(1);

    $this->assertNotNull($courier);
    $this->assertSame(1, $courier['id']);

    foreach ($courier['documents'] as $document) {
      $this->assertSame(1, $document['courier_id']);
    }

    foreach ($courier['work_history'] as $history) {
      $this->assertSame(1, $history['courier_id']);
    }

    $this->assertArrayNotHasKey('earnings', $courier);

    foreach ($courier['bank_accounts'] as $account) {
      $this->assertSame(1, $account['courier_id']);
    }

    foreach ($courier['vehicles'] as $vehicle) {
      $this->assertSame(1, $vehicle['courier_id']);
    }

    foreach ($courier['activities'] as $activity) {
      $this->assertSame(1, $activity['courier_id']);
    }

    $fileNames = collect($courier['documents'])->pluck('file_name')->all();
    $this->assertContains('ahmet-yildiz-kimlik.pdf', $fileNames);
    $this->assertNotContains('murat-kaya-kimlik.pdf', $fileNames);
  }

  public function test_business_show_payload_only_includes_own_related_data(): void
  {
    $business = BusinessDummyData::showPayload(1);

    $this->assertNotNull($business);
    $this->assertSame(1, $business['id']);

    foreach ($business['contacts'] as $contact) {
      $this->assertSame(1, $contact['business_id']);
    }

    foreach ($business['contracts'] as $contract) {
      $this->assertSame(1, $contract['business_id']);
    }

    foreach ($business['assignments'] as $assignment) {
      $this->assertSame(1, $assignment['business_id']);
    }

    $this->assertArrayNotHasKey('earnings', $business);

    foreach ($business['documents'] as $document) {
      $this->assertSame(1, $document['business_id']);
    }

    foreach ($business['activities'] as $activity) {
      $this->assertSame(1, $activity['business_id']);
    }
  }

  public function test_agency_show_payload_only_includes_own_related_data(): void
  {
    $agency = AgencyDummyData::showPayload(1);

    $this->assertNotNull($agency);
    $this->assertSame(1, $agency['id']);

    foreach ($agency['contacts'] as $contact) {
      $this->assertSame(1, $contact['agency_id']);
    }

    foreach ($agency['couriers'] as $record) {
      $this->assertSame(1, $record['agency_id']);
    }

    foreach ($agency['contracts'] as $contract) {
      $this->assertSame(1, $contract['agency_id']);
    }

    $this->assertArrayNotHasKey('earnings', $agency);

    foreach ($agency['documents'] as $document) {
      $this->assertSame(1, $document['agency_id']);
    }

    foreach ($agency['activities'] as $activity) {
      $this->assertSame(1, $activity['agency_id']);
    }
  }

  public function test_entity_edit_pages_open_with_prefilled_forms(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $business = $this->createBusiness($user, [
      'company_name' => 'Burger House Gıda Ltd. Şti.',
      'brand_name' => 'Burger House',
    ]);
    $courier = $this->createCourier($user, [
      'first_name' => 'Ahmet',
      'last_name' => 'Yıldız',
      'full_name' => 'Ahmet Yıldız',
    ]);
    $agency = $this->createAgency($user, [
      'company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.',
    ]);

    $this->actingAs($user)->get(route('businesses.edit', $business->id))
      ->assertOk()
      ->assertSee('İşletmeyi Düzenle')
      ->assertSee('Burger House Gıda Ltd. Şti.')
      ->assertSee('Güncelle')
      ->assertSee('business-form');

    $this->actingAs($user)->get(route('couriers.edit', $courier->id))
      ->assertOk()
      ->assertSee('Kuryeyi Düzenle')
      ->assertSee('Ahmet')
      ->assertSee('Yıldız')
      ->assertSee('courier-form');

    $this->actingAs($user)->get(route('agencies.edit', $agency->id))
      ->assertOk()
      ->assertSee('Acenteyi Düzenle')
      ->assertSee('Hızlı Kurye Acentesi Ltd. Şti.')
      ->assertSee('agency-form');
  }

  public function test_entity_edit_pages_return_404_for_unknown_ids(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->get(route('businesses.edit', 999))->assertNotFound();
    $this->actingAs($user)->get(route('couriers.edit', 999))->assertNotFound();
    $this->actingAs($user)->get(route('agencies.edit', 999))->assertNotFound();
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

  /**
   * @param  array<string, mixed>  $overrides
   */
  private function createCourier(User $user, array $overrides = []): Courier
  {
    $vehicleTypeId = VehicleType::query()->where('code', 'motor')->value('id');

    return Courier::factory()->create(array_merge([
      'vehicle_type_id' => $vehicleTypeId,
      'created_by' => $user->id,
    ], $overrides));
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
