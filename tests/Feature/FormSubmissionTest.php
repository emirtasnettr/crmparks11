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
    $response->assertSessionHas(
      'form_success',
      'Talebiniz alınmıştır, ilgili ekibimiz en kısa süre içerisinde sizlerle iletişime geçecektir.'
    );

    $landing = $this->followRedirects($response);
    $landing->assertOk();
    $landing->assertSee('Talebiniz alındı');
    $landing->assertSee('Talebiniz alınmıştır, ilgili ekibimiz en kısa süre içerisinde sizlerle iletişime geçecektir.');
    $landing->assertSee('Tamam');
    $landing->assertSee('role="dialog"', false);

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

  public function test_admin_can_view_submission_and_add_notes(): void
  {
    $user = User::factory()->create(['name' => 'Operasyon Admin']);
    $user->assignRole('super_admin');

    $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Not Formu',
      'status' => 'active',
    ]);

    $this->actingAs($user)->put(route('form-builder.update', 1), [
      'name' => 'Not Formu',
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
      'name' => 'Not Landing',
      'slug' => 'not-landing',
      'status' => 'active',
      'form_id' => 1,
    ]);

    $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
      'name' => 'Not Landing',
      'slug' => 'not-landing',
      'status' => 'active',
      'form_id' => 1,
    ]);

    $this->post(route('landing.submit', 'not-landing'), [
      'ad_soyad' => 'Ayşe Yılmaz',
    ]);

    $list = $this->actingAs($user)->get(route('form-builder.submissions.index', 1));
    $list->assertOk();
    $list->assertSee('Görüntüle');
    $list->assertSee(route('form-builder.submissions.show', [1, 1]), false);

    $show = $this->actingAs($user)->get(route('form-builder.submissions.show', [1, 1]));
    $show->assertOk();
    $show->assertSee('Ayşe Yılmaz');
    $show->assertSee('Notlar');
    $show->assertSee('Henüz not yok');

    $storeNote = $this->actingAs($user)->post(route('form-builder.submissions.notes.store', [1, 1]), [
      'body' => 'Aday arandı, dönüş bekleniyor.',
    ]);

    $storeNote->assertRedirect(route('form-builder.submissions.show', [1, 1]));
    $storeNote->assertSessionHas('success');

    $this->assertDatabaseCount('form_submission_notes', 1);
    $this->assertDatabaseHas('form_submission_notes', [
      'form_submission_id' => 1,
      'user_id' => $user->id,
      'body' => 'Aday arandı, dönüş bekleniyor.',
    ]);

    $secondNote = $this->actingAs($user)->post(route('form-builder.submissions.notes.store', [1, 1]), [
      'body' => 'İkinci görüşme planlandı.',
    ]);
    $secondNote->assertRedirect(route('form-builder.submissions.show', [1, 1]));
    $this->assertDatabaseCount('form_submission_notes', 2);

    $updatedShow = $this->actingAs($user)->get(route('form-builder.submissions.show', [1, 1]));
    $updatedShow->assertOk();
    $updatedShow->assertSee('Aday arandı, dönüş bekleniyor.');
    $updatedShow->assertSee('İkinci görüşme planlandı.');
    $updatedShow->assertSee('Operasyon Admin');
    $updatedShow->assertDontSee('Henüz not yok');
  }
}
