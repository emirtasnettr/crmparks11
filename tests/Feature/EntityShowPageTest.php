<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityShowPageTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
  }

  public function test_business_show_accepts_custom_date_range(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('businesses.show', [
      'id' => 1,
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

    $stats = \App\Modules\Business\Data\BusinessOverviewStats::forBusiness(
      1,
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

    $response = $this->actingAs($user)->get(route('businesses.show', 1));
    $response->assertSee(\App\Core\Helpers\MoneyCalculator::formatVatAmount($stats['received_per_package']), false);
    $response->assertSee('KDV hariç');
  }

  public function test_business_show_page_displays_profile_card(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('businesses.show', 1));

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

    $response = $this->actingAs($user)->get(route('couriers.show', 1));

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

    $response = $this->actingAs($user)->get(route('agencies.show', 1));

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

    $this->actingAs($user)->get(route('businesses.edit', 1))
      ->assertOk()
      ->assertSee('İşletmeyi Düzenle')
      ->assertSee('Burger House Gıda Ltd. Şti.')
      ->assertSee('Güncelle')
      ->assertSee('business-form');

    $this->actingAs($user)->get(route('couriers.edit', 1))
      ->assertOk()
      ->assertSee('Kuryeyi Düzenle')
      ->assertSee('Ahmet')
      ->assertSee('Yıldız')
      ->assertSee('courier-form');

    $this->actingAs($user)->get(route('agencies.edit', 1))
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
}
