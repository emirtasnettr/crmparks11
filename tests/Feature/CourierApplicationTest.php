<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_operations_roles_can_access_courier_applications_menu_and_inbox(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('operations_manager');

        $dashboard = $this->actingAs($manager)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('Kurye Başvuruları');
        $dashboard->assertSee(route('courier-applications.index'), false);
        $dashboard->assertDontSee(route('form-builder.index'), false);

        $index = $this->actingAs($manager)->get(route('courier-applications.index'));
        $index->assertOk();
        $index->assertSee('Kurye Başvuruları');

        $staff = User::factory()->create();
        $staff->assignRole('operations_staff');

        $staffIndex = $this->actingAs($staff)->get(route('courier-applications.index'));
        $staffIndex->assertOk();
    }

    public function test_general_and_sales_managers_can_access_courier_applications(): void
    {
        foreach (['general_manager', 'sales_manager'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $dashboard = $this->actingAs($user)->get(route('dashboard'));
            $dashboard->assertOk();
            $dashboard->assertSee('Kurye Başvuruları');
            $dashboard->assertSee(route('courier-applications.index'), false);

            $this->actingAs($user)->get(route('courier-applications.index'))->assertOk();
        }
    }

    public function test_operations_staff_can_view_submission_but_not_form_builder(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->createActiveFormLandingAndSubmission($admin, 'Ops Aday', 'ops-landing');

        $staff = User::factory()->create();
        $staff->assignRole('operations_staff');

        $this->actingAs($staff)->get(route('form-builder.index'))->assertForbidden();

        $index = $this->actingAs($staff)->get(route('courier-applications.index'));
        $index->assertOk();
        $index->assertSee('Ops Aday');
        $index->assertSee('Görüntüle');

        $show = $this->actingAs($staff)->get(route('courier-applications.show', 1));
        $show->assertOk();
        $show->assertSee('Ops Aday');
        $show->assertSee('Notlar');
    }

    public function test_finance_officer_cannot_access_courier_applications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('finance_officer');

        $this->actingAs($user)->get(route('courier-applications.index'))->assertForbidden();
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
