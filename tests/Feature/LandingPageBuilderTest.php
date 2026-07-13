<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageBuilderTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('public');
  }

  public function test_landing_page_builder_index_requires_authentication(): void
  {
    $response = $this->get(route('landing-page-builder.index'));

    $response->assertRedirect(route('login'));
  }

  public function test_super_admin_can_view_landing_page_builder_index(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('landing-page-builder.index'));

    $response->assertOk();
    $response->assertSee('Landing Page Builder');
    $response->assertSee('Yeni Landing Page');
  }

  public function test_super_admin_can_create_edit_and_publish_landing_page(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $formResponse = $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Başvuru Formu',
      'description' => 'Test',
      'status' => 'active',
    ]);

    $formResponse->assertRedirect(route('form-builder.edit', 1));

    $this->actingAs($user)->put(route('form-builder.update', 1), [
      'name' => 'Başvuru Formu',
      'description' => 'Test',
      'status' => 'active',
      'fields_json' => json_encode([
        [
          'id' => 'field_1',
          'type' => 'text',
          'label' => 'Ad Soyad',
          'name' => 'ad_soyad',
          'placeholder' => 'Adınız',
          'help_text' => '',
          'required' => true,
          'width' => 'full',
          'options' => [],
        ],
      ]),
    ]);

    $createResponse = $this->actingAs($user)->post(route('landing-page-builder.store'), [
      'name' => 'Kurye Başvuru Sayfası',
      'slug' => 'kurye-basvuru',
      'status' => 'draft',
    ]);

    $createResponse->assertRedirect(route('landing-page-builder.edit', 1));
    $createResponse->assertSessionHas('success');

    $updateResponse = $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
      'name' => 'Kurye Başvuru Sayfası',
      'slug' => 'kurye-basvuru',
      'status' => 'active',
      'title' => 'Kurye Ol',
      'content' => '<p>Ekibimize <strong>katılın</strong>.</p><script>alert(1)</script>',
      'form_id' => 1,
      'meta_title' => 'Kurye Başvuru | CRMLogi',
      'meta_description' => 'Kurye başvuru formu ile hemen başvurun.',
      'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
    ]);

    $updateResponse->assertRedirect(route('landing-page-builder.edit', 1));
    $updateResponse->assertSessionHas('success');

    $publicResponse = $this->get(route('landing.show', 'kurye-basvuru'));

    $publicResponse->assertOk();
    $publicResponse->assertSee('Kurye Ol', false);
    $publicResponse->assertSee('Ekibimize', false);
    $publicResponse->assertSee('katılın', false);
    $publicResponse->assertSee('<strong>katılın</strong>', false);
    $publicResponse->assertDontSee('<script>', false);
    $publicResponse->assertSee('Ad Soyad', false);
    $publicResponse->assertSee('Kurye Başvuru | CRMLogi', false);
    $publicResponse->assertSee('Kurye başvuru formu ile hemen başvurun.', false);

    $indexResponse = $this->actingAs($user)->get(route('landing-page-builder.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('Kurye Başvuru Sayfası');
    $indexResponse->assertSee('Yayında');
  }

  public function test_draft_landing_page_is_not_publicly_accessible(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->post(route('landing-page-builder.store'), [
      'name' => 'Taslak Sayfa',
      'slug' => 'taslak-sayfa',
      'status' => 'draft',
    ]);

    $response = $this->get(route('landing.show', 'taslak-sayfa'));

    $response->assertNotFound();
  }

  public function test_sidebar_contains_landing_page_builder_link_for_super_admin(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee(route('landing-page-builder.index'), false);
    $response->assertSee('Landing Page Builder');
  }

  public function test_operations_specialist_cannot_access_landing_page_builder(): void
  {
    $user = User::factory()->create();
    $user->assignRole('operations_specialist');

    $response = $this->actingAs($user)->get(route('landing-page-builder.index'));

    $response->assertForbidden();
  }
}
