<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->seed(RoleAndPermissionSeeder::class);
  }

  public function test_form_builder_index_requires_authentication(): void
  {
    $response = $this->get(route('form-builder.index'));

    $response->assertRedirect(route('login'));
  }

  public function test_super_admin_can_view_form_builder_index(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('form-builder.index'));

    $response->assertOk();
    $response->assertSee('Form Builder');
    $response->assertSee('Yeni Form');
  }

  public function test_super_admin_can_create_and_edit_form(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->post(route('form-builder.store'), [
      'name' => 'Kurye Başvuru Formu',
      'description' => 'Test formu',
      'status' => 'draft',
    ]);

    $response->assertRedirect(route('form-builder.edit', 1));
    $response->assertSessionHas('success');

    $editResponse = $this->actingAs($user)->get(route('form-builder.edit', 1));
    $editResponse->assertOk();
    $editResponse->assertSee('Form Düzenleyici');
    $editResponse->assertSee('Kurye Başvuru Formu');
    $editResponse->assertSee('Alan Paleti');

    $updateResponse = $this->actingAs($user)->put(route('form-builder.update', 1), [
      'name' => 'Kurye Başvuru Formu',
      'description' => 'Güncellendi',
      'status' => 'active',
      'fields_json' => json_encode([
        [
          'id' => 'field_1',
          'type' => 'text',
          'label' => 'Ad Soyad',
          'name' => 'ad_soyad',
          'placeholder' => 'Adınızı girin',
          'help_text' => '',
          'required' => true,
          'width' => 'full',
          'options' => [],
        ],
      ]),
    ]);

    $updateResponse->assertRedirect(route('form-builder.edit', 1));
    $updateResponse->assertSessionHas('success');

    $indexResponse = $this->actingAs($user)->get(route('form-builder.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('Kurye Başvuru Formu');
    $indexResponse->assertSee('Yayında');
  }

  public function test_sidebar_contains_form_builder_link_for_super_admin(): void
  {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee(route('form-builder.index'), false);
    $response->assertSee('Form Builder');
  }

  public function test_operations_specialist_cannot_access_form_builder(): void
  {
    $user = User::factory()->create();
    $user->assignRole('operations_specialist');

    $response = $this->actingAs($user)->get(route('form-builder.index'));

    $response->assertForbidden();
  }
}
