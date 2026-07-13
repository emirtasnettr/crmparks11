<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_operations_roles_can_access_form_applications_without_form_builder(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('operations_specialist');

        $dashboard = $this->actingAs($manager)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('Form Başvuruları');
        $dashboard->assertSee(route('form-applications.index'), false);
        $dashboard->assertDontSee('Kurye Başvuruları');
        $dashboard->assertDontSee(route('form-builder.index'), false);

        $index = $this->actingAs($manager)->get(route('form-applications.index'));
        $index->assertOk();
        $index->assertSee('Form Başvuruları');
        $index->assertDontSee('Yeni Form');

        $staff = User::factory()->create();
        $staff->assignRole('operations_specialist');
        $this->actingAs($staff)->get(route('form-applications.index'))->assertOk();
    }

    public function test_general_and_sales_managers_can_access_form_applications(): void
    {
        foreach (['general_manager', 'sales_manager'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $dashboard = $this->actingAs($user)->get(route('dashboard'));
            $dashboard->assertOk();
            $dashboard->assertSee('Form Başvuruları');
            $dashboard->assertSee(route('form-applications.index'), false);

            $this->actingAs($user)->get(route('form-applications.index'))->assertOk();
        }
    }

    public function test_operations_specialist_can_view_forms_and_submissions_but_not_edit_forms(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->createActiveFormLandingAndSubmission($admin, 'Ops Aday', 'ops-landing');

        $staff = User::factory()->create();
        $staff->assignRole('operations_specialist');

        $this->actingAs($staff)->get(route('form-builder.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('form-builder.edit', 1))->assertForbidden();

        $index = $this->actingAs($staff)->get(route('form-applications.index'));
        $index->assertOk();
        $index->assertSee('Kurye Başvuru Formu');
        $index->assertSee('Başvuruları Gör');

        $submissions = $this->actingAs($staff)->get(route('form-applications.submissions', 1));
        $submissions->assertOk();
        $submissions->assertSee('Ops Aday');
        $submissions->assertSee('Ad Soyad');
        $submissions->assertDontSee('IP Adresi');
        $submissions->assertDontSee('>IP</th>', false);

        $show = $this->actingAs($staff)->get(route('form-applications.show', [1, 1]));
        $show->assertOk();
        $show->assertSee('Ops Aday');
        $show->assertSee('Notlar');
        $show->assertDontSee('IP Adresi');
    }

    public function test_courier_cannot_access_form_applications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('courier');

        $this->actingAs($user)->get(route('form-applications.index'))->assertForbidden();
    }

    private function createActiveFormLandingAndSubmission(User $user, string $name, string $slug): void
    {
        $this->actingAs($user)->post(route('form-builder.store'), [
            'name' => 'Kurye Başvuru Formu',
            'status' => 'active',
        ]);

        $this->actingAs($user)->put(route('form-builder.update', 1), [
            'name' => 'Kurye Başvuru Formu',
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
            'name' => 'Ops Landing',
            'slug' => $slug,
            'status' => 'active',
            'form_id' => 1,
        ]);

        $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
            'name' => 'Ops Landing',
            'slug' => $slug,
            'status' => 'active',
            'form_id' => 1,
        ]);

        $this->post(route('landing.submit', $slug), [
            'ad_soyad' => $name,
        ])->assertRedirect();
    }
}
