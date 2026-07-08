<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Setting\Data\SettingsDefaults;
use App\Modules\Setting\Services\AppBrandingService;
use App\Modules\Setting\Services\SettingsManager;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
  }

  public function test_settings_index_requires_authentication(): void
  {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('login'));
  }

  public function test_super_admin_can_view_settings(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertOk();
    $response->assertSee('Sistem Ayarları');
    $response->assertSee('Genel Ayarlar');
    $response->assertSee('Firma Bilgileri');
    $response->assertSee('Logo & Görsel Ayarları');
    $response->assertSee('Güvenlik Ayarları');
    $response->assertSee('Politika Ayarları');
    $response->assertSee('Kaydet');
    $response->assertSee('Varsayılana Döndür');
    $response->assertSee('CRMLog');
  }

  public function test_general_manager_cannot_view_settings(): void
  {
    $user = User::factory()->create();
    $user->assignRole('general_manager');

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertForbidden();
  }

  public function test_settings_sections_are_navigable(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('settings.index', ['section' => 'finance']));

    $response->assertOk();
    $response->assertSee('Finans Ayarları');
    $response->assertSee('Varsayılan KDV');
  }

  public function test_super_admin_can_save_general_settings(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->put(route('settings.update', 'general'), [
      'system_name' => 'CRMLog Pro',
      'short_description' => 'Test',
      'company_title' => 'Test A.Ş.',
      'phone' => '0212 000 00 00',
      'email' => 'test@crmlog.test',
      'website' => 'https://crmlog.test',
      'default_locale' => 'tr',
      'timezone' => 'Europe/Istanbul',
      'currency' => 'TRY',
    ]);

    $response->assertRedirect(route('settings.index', ['section' => 'general']));
    $response->assertSessionHas('success');

    $manager = app(SettingsManager::class);
    $this->assertSame('CRMLog Pro', $manager->group('general')->all()['system_name']);
  }

  public function test_super_admin_can_reset_settings_group(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->put(route('settings.update', 'general'), [
      'system_name' => 'Özel İsim',
      'short_description' => 'Test',
      'company_title' => 'Test',
      'phone' => '0212',
      'email' => 'test@crmlog.test',
      'website' => 'https://crmlog.test',
      'default_locale' => 'tr',
      'timezone' => 'Europe/Istanbul',
      'currency' => 'TRY',
    ]);

    $response = $this->actingAs($user)->post(route('settings.reset', 'general'));

    $response->assertRedirect(route('settings.index', ['section' => 'general']));

    $manager = app(SettingsManager::class);
    $this->assertSame(SettingsDefaults::general()['system_name'], $manager->group('general')->all()['system_name']);
  }

  public function test_super_admin_can_upload_branding_logo(): void
  {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->call(
      'PUT',
      route('settings.update', 'branding'),
      ['footer_logo_enabled' => 1],
      [],
      ['logo' => UploadedFile::fake()->image('logo.png')],
    );

    $response->assertRedirect(route('settings.index', ['section' => 'branding']));
    $response->assertSessionHas('success');

    $manager = app(SettingsManager::class);
    $logoPath = $manager->group('branding')->all()['logo_path'];

    $this->assertNotNull($logoPath);
    Storage::disk('public')->assertExists($logoPath);

    $media = app(\App\Modules\Setting\Services\SettingsMediaService::class);
    $this->assertSame('/storage/'.$logoPath, $media->url($logoPath));
  }

  public function test_uploaded_logo_appears_in_application_sidebar(): void
  {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->call(
      'PUT',
      route('settings.update', 'branding'),
      ['footer_logo_enabled' => 1],
      [],
      ['logo' => UploadedFile::fake()->image('logo.png')],
    );

    $logoPath = app(SettingsManager::class)->group('branding')->all()['logo_path'];

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('/storage/'.$logoPath, false);
    $response->assertDontSee('Operasyon Yönetimi', false);
  }

  public function test_system_section_is_read_only(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('settings.index', ['section' => 'system']));

    $response->assertOk();
    $response->assertSee('salt okunurdur');
    $response->assertDontSee('Kaydet');
    $response->assertDontSee('id="settings-form"', false);
  }
}
