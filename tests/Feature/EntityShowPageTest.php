<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\District;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\PricingModelType;
use App\Models\User;
use App\Models\VehicleType;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Agency\Services\AgencyPresenter;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Business\Models\BusinessPricing;
use App\Modules\ShiftPlanning\Models\BusinessShift;
use App\Modules\ShiftPlanning\Models\BusinessShiftCourier;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Services\CourierPresenter;
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
    $courier = $this->createCourier($user);
    $pricingModel = PricingModelType::query()->where('code', 'per_package')->firstOrFail();

    BusinessPricing::query()->create([
      'business_id' => $business->id,
      'pricing_model_type_id' => $pricingModel->id,
      'customer_unit_price' => 48.00,
      'courier_unit_price' => 36.00,
      'effective_from' => now()->toDateString(),
      'is_active' => true,
      'created_by' => $user->id,
    ]);

    $shift = BusinessShift::query()->create([
      'business_id' => $business->id,
      'name' => 'Aktif',
      'start_time' => '09:00',
      'end_time' => '17:00',
      'required_headcount' => 1,
      'start_date' => '2026-07-01',
      'end_date' => null,
      'is_active' => true,
      'created_by' => $user->id,
    ]);
    BusinessShiftCourier::query()->create([
      'business_shift_id' => $shift->id,
      'courier_id' => $courier->id,
    ]);

    $stats = \App\Modules\Business\Data\BusinessOverviewStats::forBusiness(
      $business->id,
      \Carbon\Carbon::parse('2026-07-02'),
      \Carbon\Carbon::parse('2026-07-08'),
    );

    $this->assertSame(48.0, $stats['received_per_package']);
    $this->assertSame(36.0, $stats['courier_per_package']);
    $this->assertSame(1, $stats['active_couriers']);
    $this->assertSame(12.0, $stats['net_per_package']);
    $this->assertSame('48,00 ₺', $stats['received_per_package_formatted']);

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
    Contract::factory()->create([
      'contractable_type' => Business::class,
      'contractable_id' => $business->id,
      'contract_type_id' => ContractType::query()->where('code', 'service')->value('id'),
      'contract_number' => 'SZL-2026-001',
      'created_by' => $user->id,
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
    $response->assertSee('Fatura Periyodu');
    $response->assertSee('İlk Fatura Tarihi');
  }

  public function test_operations_specialist_cannot_open_business_pages(): void
  {
    $user = User::factory()->create();
    $user->assignRole('operations_specialist');
    $business = $this->createBusiness($user, [
      'company_name' => 'Ops Gizli Fiyat Ltd.',
      'brand_name' => 'Ops Gizli',
    ]);

    $show = $this->actingAs($user)->get(route('businesses.show', $business->id));
    $show->assertOk();
    $show->assertDontSee('Atanan Kuryeler');
    $this->assertStringNotContainsString("setTab('contracts')", $show->getContent());
    $this->assertStringNotContainsString("setTab('documents')", $show->getContent());
    $this->assertStringNotContainsString("setTab('contacts')", $show->getContent());
    $this->assertStringNotContainsString("setTab('activities')", $show->getContent());

    $this->actingAs($user)->get(route('businesses.index'))->assertOk();
    $this->actingAs($user)->get(route('businesses.contracts.index'))->assertForbidden();
    $this->actingAs($user)->get(route('businesses.documents.index'))->assertForbidden();
    $this->actingAs($user)->get(route('businesses.contacts.index'))->assertForbidden();
    $this->actingAs($user)->get(route('businesses.activities.index'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.contract-expiry'))->assertForbidden();
  }

  public function test_business_show_uses_pricing_model_labels_for_hourly(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $business = $this->createBusiness($user, [
      'company_name' => 'Saatlik Lojistik A.Ş.',
      'brand_name' => 'Saatlik Lojistik',
    ]);

    $pricingModel = PricingModelType::query()->where('code', 'hourly')->firstOrFail();

    BusinessPricing::query()->where('business_id', $business->id)->update(['is_active' => false]);
    BusinessPricing::query()->create([
      'business_id' => $business->id,
      'pricing_model_type_id' => $pricingModel->id,
      'customer_unit_price' => 290.00,
      'courier_unit_price' => 250.00,
      'effective_from' => now()->toDateString(),
      'is_active' => true,
      'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('businesses.show', $business->id));

    $response->assertOk();
    $response->assertSee('Saatlik Alınan');
    $response->assertSee('Saatlik Kuryeye Verilen');
    $response->assertSee('Saatlik Net Kazanç');
    $response->assertSee('saatlik göstergeler');
    $response->assertSee('İşletmeden Saatlik Ücret');
    $response->assertSee('Kuryeye Saatlik Ücret');
    $response->assertDontSee('Paket Başı Alınan');
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
    $response->assertSee('courierDocumentPage(', false);
    $response->assertSee('courierBankAccountPage(', false);
    $response->assertSee('courierVehiclePage(', false);
    $response->assertSee('courierId', false);
    $response->assertDontSee('courierDocumentPage(@js(', false);
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
    $response->assertDontSee('Aylık Hakediş');
    $response->assertSee("agencyContactPage(JSON.parse('{\\u0022agencyId\\u0022:{$agency->id}}'))", false);
    $response->assertDontSee('agencyContactPage(@js(', false);
  }

  public function test_agency_show_lists_unassigned_couriers_in_assign_modal(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $agency = $this->createAgency($user);

    $this->createCourier($user, [
      'full_name' => 'Atanabilir Kurye',
      'phone' => '0532 999 88 77',
      'agency_id' => null,
      'courier_type' => 'independent',
    ]);

    $this->createCourier($user, [
      'full_name' => 'Başka Acentede Kurye',
      'phone' => '0533 111 22 33',
      'agency_id' => $agency->id,
      'courier_type' => 'agency',
    ]);

    $response = $this->actingAs($user)->get(route('agencies.show', $agency->id));

    $response->assertOk();
    $response->assertSee('Atanabilir Kurye — 0532 999 88 77');
    $response->assertDontSee('Başka Acentede Kurye — 0533 111 22 33');
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
    $user = User::factory()->create();
    $courierA = $this->createCourier($user, ['full_name' => 'Ahmet Yıldız']);
    $courierB = $this->createCourier($user, ['full_name' => 'Murat Kaya']);
    $categoryId = DocumentCategory::query()->where('code', 'identity')->value('id');

    Document::factory()->create([
      'documentable_type' => Courier::class,
      'documentable_id' => $courierA->id,
      'document_category_id' => $categoryId,
      'original_name' => 'ahmet-yildiz-kimlik.pdf',
      'uploaded_by' => $user->id,
    ]);

    Document::factory()->create([
      'documentable_type' => Courier::class,
      'documentable_id' => $courierB->id,
      'document_category_id' => $categoryId,
      'original_name' => 'murat-kaya-kimlik.pdf',
      'uploaded_by' => $user->id,
    ]);

    $payload = app(CourierPresenter::class)->showPayload($courierA->fresh());

    $this->assertSame($courierA->id, $payload['id']);

    foreach ($payload['documents'] as $document) {
      $this->assertSame($courierA->id, $document['courier_id']);
    }

    $fileNames = collect($payload['documents'])->pluck('file_name')->all();
    $this->assertContains('ahmet-yildiz-kimlik.pdf', $fileNames);
    $this->assertNotContains('murat-kaya-kimlik.pdf', $fileNames);
  }

  public function test_business_show_payload_only_includes_own_related_data(): void
  {
    $user = User::factory()->create();
    $businessA = $this->createBusiness($user, ['company_name' => 'Burger House Gıda Ltd. Şti.']);
    $businessB = $this->createBusiness($user, ['company_name' => 'Napoli Pizza Restoran A.Ş.']);
    $contractTypeId = ContractType::query()->value('id');

    BusinessContact::factory()->create([
      'business_id' => $businessA->id,
      'full_name' => 'Mehmet Yılmaz',
    ]);

    BusinessContact::factory()->create([
      'business_id' => $businessB->id,
      'full_name' => 'Ali Veli',
    ]);

    Contract::factory()->create([
      'contractable_type' => Business::class,
      'contractable_id' => $businessA->id,
      'contract_type_id' => $contractTypeId,
      'contract_number' => 'BRG-2026-001',
    ]);

    Contract::factory()->create([
      'contractable_type' => Business::class,
      'contractable_id' => $businessB->id,
      'contract_type_id' => $contractTypeId,
      'contract_number' => 'NPL-2026-001',
    ]);

    $payload = app(BusinessPresenter::class)->showPayload($businessA->fresh());

    $this->assertSame($businessA->id, $payload['id']);

    foreach ($payload['contacts'] as $contact) {
      $this->assertSame($businessA->id, $contact['business_id']);
    }

    foreach ($payload['contracts'] as $contract) {
      $this->assertSame($businessA->id, $contract['business_id']);
    }

    $contactNames = collect($payload['contacts'])->pluck('full_name')->all();
    $this->assertContains('Mehmet Yılmaz', $contactNames);
    $this->assertNotContains('Ali Veli', $contactNames);
  }

  public function test_agency_show_payload_only_includes_own_related_data(): void
  {
    $user = User::factory()->create();
    $agencyA = $this->createAgency($user, ['company_name' => 'Hızlı Kurye Acentesi Ltd. Şti.']);
    $agencyB = $this->createAgency($user, ['company_name' => 'Metro Lojistik A.Ş.']);
    $contractTypeId = ContractType::query()->value('id');
    $categoryId = DocumentCategory::query()->where('code', 'identity')->value('id');

    AgencyContact::factory()->create([
      'agency_id' => $agencyA->id,
      'full_name' => 'Ayşe Korkmaz',
    ]);

    AgencyContact::factory()->create([
      'agency_id' => $agencyB->id,
      'full_name' => 'Deniz Polat',
    ]);

    $this->createCourier($user, [
      'full_name' => 'Emre Demir',
      'agency_id' => $agencyA->id,
      'courier_type' => 'agency',
    ]);

    $this->createCourier($user, [
      'full_name' => 'Burak Şen',
      'agency_id' => $agencyB->id,
      'courier_type' => 'agency',
    ]);

    Contract::factory()->create([
      'contractable_type' => Agency::class,
      'contractable_id' => $agencyA->id,
      'contract_type_id' => $contractTypeId,
      'contract_number' => 'ACS-2026-001',
    ]);

    Document::factory()->create([
      'documentable_type' => Agency::class,
      'documentable_id' => $agencyA->id,
      'document_category_id' => $categoryId,
      'original_name' => 'hizli-kurye-sozlesme.pdf',
      'uploaded_by' => $user->id,
    ]);

    Document::factory()->create([
      'documentable_type' => Agency::class,
      'documentable_id' => $agencyB->id,
      'document_category_id' => $categoryId,
      'original_name' => 'metro-lojistik-sozlesme.pdf',
      'uploaded_by' => $user->id,
    ]);

    $payload = app(AgencyPresenter::class)->showPayload($agencyA->fresh());

    $this->assertSame($agencyA->id, $payload['id']);

    foreach ($payload['contacts'] as $contact) {
      $this->assertSame($agencyA->id, $contact['agency_id']);
    }

    foreach ($payload['couriers'] as $record) {
      $this->assertSame($agencyA->id, $record['agency_id']);
    }

    foreach ($payload['contracts'] as $contract) {
      $this->assertSame($agencyA->id, $contract['agency_id']);
    }

    foreach ($payload['documents'] as $document) {
      $this->assertSame($agencyA->id, $document['agency_id']);
    }

    $courierNames = collect($payload['couriers'])->pluck('courier_name')->all();
    $this->assertContains('Emre Demir', $courierNames);
    $this->assertNotContains('Burak Şen', $courierNames);
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
