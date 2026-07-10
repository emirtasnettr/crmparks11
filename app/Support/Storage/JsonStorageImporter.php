<?php

namespace App\Support\Storage;

use App\Modules\FormBuilder\Models\Form;
use App\Modules\FormBuilder\Models\FormSubmission;
use App\Modules\LandingPage\Models\LandingPage;
use App\Modules\Policy\Models\Policy;
use App\Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\Storage;

class JsonStorageImporter
{
  /**
   * @return array<string, int>
   */
  public function importIfEmpty(): array
  {
    return [
      'forms' => $this->importForms(),
      'landing_pages' => $this->importLandingPages(),
      'submissions' => $this->importFormSubmissions(),
      'policies' => $this->importPolicies(),
      'settings' => $this->importSettings(),
    ];
  }

  private function importForms(): int
  {
    if (Form::query()->exists()) {
      return 0;
    }

    $path = 'form-builder/forms.json';

    if (! Storage::disk('local')->exists($path)) {
      return 0;
    }

    $decoded = json_decode(Storage::disk('local')->get($path), true);

    if (! is_array($decoded)) {
      return 0;
    }

    $imported = 0;

    foreach ($decoded as $form) {
      if (! is_array($form) || empty($form['id'])) {
        continue;
      }

      Form::query()->updateOrCreate(
        ['id' => (int) $form['id']],
        [
          'uuid' => $form['uuid'] ?? 'frm-import-'.(int) $form['id'],
          'name' => $form['name'] ?? 'Form',
          'slug' => $form['slug'] ?? 'form-'.(int) $form['id'],
          'description' => $form['description'] ?? '',
          'status' => $form['status'] ?? 'draft',
          'fields' => $form['fields'] ?? [],
          'created_at' => $form['created_at'] ?? now(),
          'updated_at' => $form['updated_at'] ?? now(),
        ],
      );

      $imported++;
    }

    return $imported;
  }

  private function importFormSubmissions(): int
  {
    if (FormSubmission::query()->exists()) {
      return 0;
    }

    $directory = 'form-builder/submissions';

    if (! Storage::disk('local')->exists($directory)) {
      return 0;
    }

    $imported = 0;

    foreach (Storage::disk('local')->files($directory) as $file) {
      if (! str_ends_with($file, '.json')) {
        continue;
      }

      $formId = (int) basename($file, '.json');

      if ($formId < 1) {
        continue;
      }

      $decoded = json_decode(Storage::disk('local')->get($file), true);

      if (! is_array($decoded)) {
        continue;
      }

      foreach ($decoded as $submission) {
        if (! is_array($submission) || empty($submission['id'])) {
          continue;
        }

        if (! Form::query()->whereKey($formId)->exists()) {
          continue;
        }

        $landingPageId = $submission['landing_page_id'] ?? null;

        if ($landingPageId !== null && ! LandingPage::query()->whereKey($landingPageId)->exists()) {
          $landingPageId = null;
        }

        FormSubmission::query()->updateOrCreate(
          [
            'id' => (int) $submission['id'],
            'form_id' => $formId,
          ],
          [
            'landing_page_id' => $landingPageId,
            'landing_page_slug' => $submission['landing_page_slug'] ?? null,
            'landing_page_name' => $submission['landing_page_name'] ?? null,
            'data' => $submission['data'] ?? [],
            'ip_address' => $submission['ip_address'] ?? null,
            'user_agent' => $submission['user_agent'] ?? null,
            'submitted_at' => $submission['submitted_at'] ?? now(),
          ],
        );

        $imported++;
      }
    }

    return $imported;
  }

  private function importLandingPages(): int
  {
    if (LandingPage::query()->exists()) {
      return 0;
    }

    $path = 'landing-page-builder/pages.json';

    if (! Storage::disk('local')->exists($path)) {
      return 0;
    }

    $decoded = json_decode(Storage::disk('local')->get($path), true);

    if (! is_array($decoded)) {
      return 0;
    }

    $imported = 0;

    foreach ($decoded as $page) {
      if (! is_array($page) || empty($page['id'])) {
        continue;
      }

      LandingPage::query()->updateOrCreate(
        ['id' => (int) $page['id']],
        [
          'uuid' => $page['uuid'] ?? 'lp-import-'.(int) $page['id'],
          'name' => $page['name'] ?? 'Landing Page',
          'slug' => $page['slug'] ?? 'landing-'.(int) $page['id'],
          'status' => $page['status'] ?? 'draft',
          'hero_image_path' => $page['hero_image_path'] ?? null,
          'title' => $page['title'] ?? '',
          'content' => $page['content'] ?? '',
          'form_id' => $page['form_id'] ?? null,
          'meta_title' => $page['meta_title'] ?? '',
          'meta_description' => $page['meta_description'] ?? '',
          'created_at' => $page['created_at'] ?? now(),
          'updated_at' => $page['updated_at'] ?? now(),
        ],
      );

      $imported++;
    }

    return $imported;
  }

  private function importPolicies(): int
  {
    if (Policy::query()->exists()) {
      return 0;
    }

    $path = 'policy-settings/policies.json';

    if (! Storage::disk('local')->exists($path)) {
      return 0;
    }

    $decoded = json_decode(Storage::disk('local')->get($path), true);

    if (! is_array($decoded)) {
      return 0;
    }

    $imported = 0;

    foreach ($decoded as $key => $policy) {
      if (! is_array($policy)) {
        continue;
      }

      Policy::query()->updateOrCreate(
        ['key' => is_string($key) ? $key : ($policy['key'] ?? '')],
        [
          'slug' => $policy['slug'] ?? $key,
          'title' => $policy['title'] ?? '',
          'content' => $policy['content'] ?? '',
          'meta_title' => $policy['meta_title'] ?? ($policy['title'] ?? ''),
          'meta_description' => $policy['meta_description'] ?? '',
          'updated_at' => $policy['updated_at'] ?? now(),
        ],
      );

      $imported++;
    }

    return $imported;
  }

  private function importSettings(): int
  {
    if (Setting::query()->exists()) {
      return 0;
    }

    $directory = 'settings/groups';

    if (! Storage::disk('local')->exists($directory)) {
      return 0;
    }

    $imported = 0;

    foreach (Storage::disk('local')->files($directory) as $file) {
      if (! str_ends_with($file, '.json')) {
        continue;
      }

      $group = basename($file, '.json');
      $decoded = json_decode(Storage::disk('local')->get($file), true);

      if (! is_array($decoded)) {
        continue;
      }

      foreach ($decoded as $key => $value) {
        Setting::query()->updateOrCreate(
          ['group' => $group, 'key' => $key],
          [
            'value' => $value,
            'type' => match (true) {
              is_bool($value) => 'boolean',
              is_int($value) => 'integer',
              is_float($value) => 'float',
              is_array($value) => 'json',
              default => 'string',
            },
          ],
        );

        $imported++;
      }
    }

    return $imported;
  }
}
