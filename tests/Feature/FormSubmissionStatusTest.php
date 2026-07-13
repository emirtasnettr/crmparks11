<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\FormBuilder\Models\FormSubmission;
use App\Modules\FormBuilder\Models\FormSubmissionStatus;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmissionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_default_statuses_are_seeded_and_new_submission_gets_yeni_basvuru(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->createActiveFormAndLanding($user);

        $response = $this->post(route('landing.submit', 'statu-landing'), [
            'ad_soyad' => 'Statü Test',
        ]);
        $response->assertRedirect();

        $default = FormSubmissionStatus::query()->where('is_default', true)->first();
        $this->assertNotNull($default);
        $this->assertSame('Yeni Başvuru', $default->name);

        $this->assertDatabaseHas('form_submissions', [
            'form_submission_status_id' => $default->id,
        ]);

        $index = $this->actingAs($user)->get(route('form-builder.index'));
        $index->assertOk();
        $index->assertSee('Statü Ayarları');
        $index->assertSee('Yeni Başvuru');
        $index->assertSee('Olumlu');
        $index->assertSee('Olumsuz');
        $index->assertSee('Ulaşılamadı');
        $index->assertSee('Kararsız');
    }

    public function test_admin_can_create_rename_and_delete_empty_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $create = $this->actingAs($user)->post(route('form-builder.statuses.store'), [
            'name' => 'Beklemede',
            'color' => 'warning',
        ]);
        $create->assertRedirect(route('form-builder.index', ['statuses' => 1]));
        $this->assertDatabaseHas('form_submission_statuses', ['name' => 'Beklemede']);

        $status = FormSubmissionStatus::query()->where('name', 'Beklemede')->firstOrFail();

        $update = $this->actingAs($user)->put(route('form-builder.statuses.update', $status->id), [
            'name' => 'İncelemede',
            'color' => 'primary',
        ]);
        $update->assertRedirect(route('form-builder.index', ['statuses' => 1]));
        $this->assertDatabaseHas('form_submission_statuses', ['id' => $status->id, 'name' => 'İncelemede']);

        $delete = $this->actingAs($user)->delete(route('form-builder.statuses.destroy', $status->id));
        $delete->assertRedirect(route('form-builder.index', ['statuses' => 1]));
        $this->assertDatabaseMissing('form_submission_statuses', ['id' => $status->id]);
    }

    public function test_status_with_submissions_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->createActiveFormAndLanding($user);
        $this->post(route('landing.submit', 'statu-landing'), ['ad_soyad' => 'Silinemez']);

        $default = FormSubmissionStatus::query()->where('is_default', true)->firstOrFail();

        $delete = $this->actingAs($user)->from(route('form-builder.index', ['statuses' => 1]))
            ->delete(route('form-builder.statuses.destroy', $default->id));

        $delete->assertRedirect();
        $delete->assertSessionHasErrors('status');
        $this->assertDatabaseHas('form_submission_statuses', ['id' => $default->id]);
    }

    public function test_admin_can_change_submission_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->createActiveFormAndLanding($user);
        $this->post(route('landing.submit', 'statu-landing'), ['ad_soyad' => 'Değişecek']);

        $submission = FormSubmission::query()->firstOrFail();
        $olumlu = FormSubmissionStatus::query()->where('slug', 'olumlu')->firstOrFail();

        $response = $this->actingAs($user)->put(
            route('form-builder.submissions.status.update', [1, $submission->id]),
            ['form_submission_status_id' => $olumlu->id]
        );

        $response->assertRedirect(route('form-builder.submissions.show', [1, $submission->id]));
        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'form_submission_status_id' => $olumlu->id,
        ]);

        $show = $this->actingAs($user)->get(route('form-builder.submissions.show', [1, $submission->id]));
        $show->assertOk();
        $show->assertSee('Olumlu');
    }

    private function createActiveFormAndLanding(User $user): void
    {
        $this->actingAs($user)->post(route('form-builder.store'), [
            'name' => 'Statü Formu',
            'status' => 'active',
        ]);

        $this->actingAs($user)->put(route('form-builder.update', 1), [
            'name' => 'Statü Formu',
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
            'name' => 'Statü Landing',
            'slug' => 'statu-landing',
            'status' => 'active',
            'form_id' => 1,
        ]);

        $this->actingAs($user)->put(route('landing-page-builder.update', 1), [
            'name' => 'Statü Landing',
            'slug' => 'statu-landing',
            'status' => 'active',
            'form_id' => 1,
        ]);
    }
}
