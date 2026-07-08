<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('public');
    Storage::disk('local')->delete('form-builder/forms.json');
    Storage::disk('local')->delete('landing-page-builder/pages.json');
    Storage::disk('local')->delete('form-builder/submissions/1.json');
  }

  public function test_public_landing_form_submission_is_stored(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Başvuru Formu',
      'status' => 'active',
    ]);

    $this->actingAs($user)->put(route('form-builder.update', 1), [
      'name' => 'Başvuru Formu',
      'status' => 'active',
      'fields_json' => json_encode([
        [
          'id' => 'field_1',
          'type' => 'text',
          'label' => 'Ad Soyad',
          'name' => 'ad_soyad',
          'placeholder' => '',
          'help_text' => '',
          'required' => true,
          'width' => 'full',
          'options' => [],
        ],
        [
          'id' => 'field_2',
          'type' => 'email',
          'label' => 'E-posta',
          'name' => 'email',
          'placeholder' => '',
          'help_text' => '',
          'required' => true,
          'width' => 'full',
          'options' => [],
        ],
      ]),
    ]);

    $this->actingAs($user)->post(route('landing-page-builder.store'), [
      'name' => 'Başvuru Landing Page',
      'slug' => 'basvuru',
      'status' => 'active',
      'form_id' => 1,
    ]);

    $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
      'name' => 'Başvuru Landing Page',
      'slug' => 'basvuru',
      'status' => 'active',
      'title' => 'Başvuru',
      'content' => '<p>Formu doldurun</p>',
      'form_id' => 1,
    ]);

    $response = $this->post(route('landing.submit', 'basvuru'), [
      'ad_soyad' => 'Ali Veli',
      'email' => 'ali@example.com',
    ]);

    $response->assertRedirect(route('landing.show', 'basvuru'));
    $response->assertSessionHas('form_success');

    $admin = $this->actingAs($user)->get(route('form-builder.submissions.index', 1));
    $admin->assertOk();
    $admin->assertSee('Ali Veli');
    $admin->assertSee('ali@example.com');
    $admin->assertSee('Excel');
    $admin->assertSee('Aktar');
  }

  public function test_super_admin_can_export_form_submissions_as_excel(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Export Formu',
      'status' => 'active',
    ]);

    $this->actingAs($user)->put(route('form-builder.update', 1), [
      'name' => 'Export Formu',
      'status' => 'active',
      'fields_json' => json_encode([
        [
          'id' => 'field_1',
          'type' => 'text',
          'label' => 'Ad Soyad',
          'name' => 'ad_soyad',
          'placeholder' => '',
          'help_text' => '',
          'required' => true,
          'width' => 'full',
          'options' => [],
        ],
      ]),
    ]);

    $this->actingAs($user)->post(route('landing-page-builder.store'), [
      'name' => 'Export Landing',
      'slug' => 'export-landing',
      'status' => 'active',
      'form_id' => 1,
    ]);

    $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
      'name' => 'Export Landing',
      'slug' => 'export-landing',
      'status' => 'active',
      'form_id' => 1,
    ]);

    $this->post(route('landing.submit', 'export-landing'), [
      'ad_soyad' => 'Test Kullanıcı',
    ]);

    $response = $this->actingAs($user)->get(route('form-builder.submissions.export', 1));

    $response->assertOk();
    $response->assertDownload();
  }

  public function test_form_index_shows_submission_count_link(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Sayım Formu',
      'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('form-builder.index'));

    $response->assertOk();
    $response->assertSee(route('form-builder.submissions.index', 1), false);
  }
}
