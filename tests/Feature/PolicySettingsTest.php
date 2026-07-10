<?php

namespace Tests\Feature;

use App\Modules\LandingPage\Models\LandingPage;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicySettingsTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
  }

  public function test_policy_settings_index_redirects_to_settings_section(): void
  {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('policy-settings.index'));

    $response->assertRedirect(route('settings.index', ['section' => 'policies']));
  }

  public function test_policy_settings_index_requires_authentication(): void
  {
    $response = $this->get(route('policy-settings.index'));

    $response->assertRedirect(route('login'));
  }

  public function test_policy_settings_index_requires_permission(): void
  {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('policy-settings.index'));

    $response->assertForbidden();
  }

  public function test_admin_can_view_and_update_policy_settings(): void
  {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $viewResponse = $this->actingAs($admin)->get(route('settings.index', ['section' => 'policies']));
    $viewResponse->assertOk();
    $viewResponse->assertSee('Politika Ayarları');
    $viewResponse->assertSee('KVKK Aydınlatma Metni');

    $updateResponse = $this->actingAs($admin)->put(route('policy-settings.update'), [
      'kvkk' => [
        'title' => 'KVKK Metni',
        'content' => '<p>Test KVKK içeriği</p>',
        'meta_title' => 'KVKK',
        'meta_description' => 'KVKK açıklama',
      ],
      'privacy' => [
        'title' => 'Gizlilik',
        'content' => '<p>Gizlilik içeriği</p>',
        'meta_title' => 'Gizlilik',
        'meta_description' => 'Gizlilik açıklama',
      ],
      'cookie' => [
        'title' => 'Çerez',
        'content' => '<p>Çerez içeriği</p>',
        'meta_title' => 'Çerez',
        'meta_description' => 'Çerez açıklama',
      ],
    ]);

    $updateResponse->assertRedirect(route('settings.index', ['section' => 'policies']));
    $updateResponse->assertSessionHas('success');

    $publicResponse = $this->get(route('policy.show', 'kvkk-aydinlatma-metni'));
    $publicResponse->assertOk();
    $publicResponse->assertSee('KVKK Metni');
    $publicResponse->assertSee('Test KVKK içeriği', false);
    $publicResponse->assertSee('KVKK Aydınlatma Metni');
    $publicResponse->assertSee('Gizlilik Politikası');
    $publicResponse->assertSee('Çerez Politikası');
  }

  public function test_landing_page_includes_policy_footer(): void
  {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    LandingPage::query()->create([
      'uuid' => 'lp-test-1',
      'name' => 'Test LP',
      'slug' => 'test-lp',
      'status' => 'active',
      'title' => 'Test',
      'content' => '',
      'form_id' => null,
      'meta_title' => 'Test',
      'meta_description' => '',
      'hero_image_path' => null,
    ]);

    $response = $this->get(route('landing.show', 'test-lp'));

    $response->assertOk();
    $response->assertSee('KVKK Aydınlatma Metni');
    $response->assertSee('Gizlilik Politikası');
    $response->assertSee('Çerez Politikası');
    $response->assertSee(route('policy.show', 'kvkk-aydinlatma-metni'), false);
  }

  public function test_unknown_policy_slug_returns_404(): void
  {
    $response = $this->get(route('policy.show', 'bilinmeyen-politika'));

    $response->assertNotFound();
  }
}
