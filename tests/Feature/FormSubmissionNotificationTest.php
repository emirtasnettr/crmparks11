<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\FormBuilder\Models\Form;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmissionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_form_persists_notification_recipients(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $recipient = User::factory()->create();
        $recipient->assignRole('sales_manager');

        $this->actingAs($admin)->post(route('form-builder.store'), [
            'name' => 'Bildirimli Form',
            'status' => 'draft',
            'notify_user_ids' => [$recipient->id],
            'notify_roles' => ['operations_specialist'],
        ])->assertRedirect(route('form-builder.edit', 1));

        $form = Form::query()->findOrFail(1);

        $this->assertSame([$recipient->id], $form->notify_user_ids);
        $this->assertSame(['operations_specialist'], $form->notify_roles);

        $this->actingAs($admin)->get(route('form-builder.edit', 1))
            ->assertOk()
            ->assertSee('Başvuru Bildirimleri')
            ->assertSee($recipient->email);
    }

    public function test_new_submission_notifies_selected_users_and_roles_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $selectedUser = User::factory()->create();
        $selectedUser->assignRole('sales_manager');

        $roleMember = User::factory()->create();
        $roleMember->assignRole('operations_specialist');

        $outsider = User::factory()->create();
        $outsider->assignRole('general_manager');

        $this->createActiveLandingForm($admin, [
            'notify_user_ids' => [$selectedUser->id],
            'notify_roles' => ['operations_specialist'],
        ]);

        $this->post(route('landing.submit', 'basvuru'), [
            'ad_soyad' => 'Ali Veli',
            'email' => 'ali@example.com',
        ])->assertRedirect(route('landing.show', 'basvuru'));

        $this->assertTrue(
            $selectedUser->fresh()->notifications()->where('data->type', 'form_submission_created')->exists()
        );
        $this->assertTrue(
            $roleMember->fresh()->notifications()->where('data->type', 'form_submission_created')->exists()
        );
        $this->assertFalse(
            $outsider->fresh()->notifications()->where('data->type', 'form_submission_created')->exists()
        );
        $this->assertFalse(
            $admin->fresh()->notifications()->where('data->type', 'form_submission_created')->exists()
        );
    }

    public function test_submission_notification_action_url_opens_application_show_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $recipient = User::factory()->create();
        $recipient->assignRole('sales_manager');

        // Seed an earlier form/submission so auto-increment IDs no longer start at 1.
        $this->createActiveLandingForm($admin, [], 'eski-form', 'eski-basvuru');
        $this->post(route('landing.submit', 'eski-basvuru'), [
            'ad_soyad' => 'Eski Kayit',
            'email' => 'eski@example.com',
        ])->assertRedirect();

        $this->createActiveLandingForm($admin, [
            'notify_user_ids' => [$recipient->id],
        ], 'yeni-form', 'yeni-basvuru');

        $this->post(route('landing.submit', 'yeni-basvuru'), [
            'ad_soyad' => 'Yeni Aday',
            'email' => 'yeni@example.com',
        ])->assertRedirect(route('landing.show', 'yeni-basvuru'));

        $notification = $recipient->fresh()->notifications()
            ->where('data->type', 'form_submission_created')
            ->latest()
            ->first();

        $this->assertNotNull($notification);

        $actionUrl = $notification->data['action_url'] ?? null;
        $this->assertIsString($actionUrl);

        $submissionId = (int) ($notification->data['meta']['submission_id'] ?? 0);
        $formId = (int) ($notification->data['meta']['form_id'] ?? 0);

        $this->assertGreaterThan(1, $submissionId);
        $this->assertSame(route('form-applications.show', [$formId, $submissionId]), $actionUrl);

        $this->actingAs($recipient)
            ->get($actionUrl)
            ->assertOk()
            ->assertSee('Yeni Aday');
    }

    public function test_new_submission_sends_no_notifications_when_recipients_empty(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $other = User::factory()->create();
        $other->assignRole('sales_manager');

        $this->createActiveLandingForm($admin);

        $this->post(route('landing.submit', 'basvuru'), [
            'ad_soyad' => 'Ali Veli',
            'email' => 'ali@example.com',
        ])->assertRedirect(route('landing.show', 'basvuru'));

        $this->assertSame(0, $admin->fresh()->notifications()->count());
        $this->assertSame(0, $other->fresh()->notifications()->count());
    }

    /**
     * @param  array<string, mixed>  $notify
     */
    private function createActiveLandingForm(
        User $admin,
        array $notify = [],
        string $formName = 'Başvuru Formu',
        string $slug = 'basvuru',
    ): void {
        $this->actingAs($admin)->post(route('form-builder.store'), array_merge([
            'name' => $formName,
            'status' => 'active',
        ], $notify))->assertRedirect();

        $form = Form::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)->put(route('form-builder.update', $form->id), array_merge([
            'name' => $formName,
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
        ], $notify))->assertRedirect();

        $this->actingAs($admin)->post(route('landing-page-builder.store'), [
            'name' => $formName.' Landing',
            'slug' => $slug,
            'status' => 'active',
            'form_id' => $form->id,
        ])->assertRedirect();

        $landing = \App\Modules\LandingPage\Models\LandingPage::query()->where('slug', $slug)->firstOrFail();

        $this->actingAs($admin)->put(route('landing-page-builder.update', $landing->id), [
            'name' => $formName.' Landing',
            'slug' => $slug,
            'status' => 'active',
            'title' => 'Başvuru',
            'content' => '<p>Formu doldurun</p>',
            'form_id' => $form->id,
        ])->assertRedirect();
    }
}
