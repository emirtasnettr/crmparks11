<?php

namespace App\Modules\FormBuilder\Repositories;

use App\Modules\FormBuilder\Models\FormSubmission;

class FormSubmissionRepository
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(int $formId): array
  {
    return FormSubmission::query()
      ->with('status')
      ->where('form_id', $formId)
      ->orderBy('id')
      ->get()
      ->map(fn (FormSubmission $submission) => $submission->toRecordArray())
      ->all();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function allAcrossForms(): array
  {
    return FormSubmission::query()
      ->with(['status', 'form'])
      ->orderByDesc('submitted_at')
      ->orderByDesc('id')
      ->get()
      ->map(function (FormSubmission $submission) {
        $record = $submission->toRecordArray();
        $record['form_name'] = $submission->form?->name;
        $record['form_status'] = $submission->form?->status;

        return $record;
      })
      ->all();
  }

  public function count(int $formId): int
  {
    return FormSubmission::query()->where('form_id', $formId)->count();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findById(int $submissionId): ?array
  {
    $submission = FormSubmission::query()
      ->with(['status', 'form'])
      ->whereKey($submissionId)
      ->first();

    if ($submission === null) {
      return null;
    }

    $record = $submission->toRecordArray();
    $record['form_name'] = $submission->form?->name;
    $record['form_status'] = $submission->form?->status;

    return $record;
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $formId, int $submissionId): ?array
  {
    $submission = FormSubmission::query()
      ->with('status')
      ->where('form_id', $formId)
      ->whereKey($submissionId)
      ->first();

    return $submission?->toRecordArray();
  }

  /**
   * @param  array<string, mixed>  $attributes
   * @return array<string, mixed>
   */
  public function create(array $attributes): array
  {
    $submission = FormSubmission::query()->create([
      'form_id' => (int) $attributes['form_id'],
      'form_submission_status_id' => $attributes['form_submission_status_id'] ?? null,
      'landing_page_id' => $attributes['landing_page_id'] ?? null,
      'landing_page_slug' => $attributes['landing_page_slug'] ?? null,
      'landing_page_name' => $attributes['landing_page_name'] ?? null,
      'data' => $attributes['data'] ?? [],
      'ip_address' => $attributes['ip_address'] ?? null,
      'user_agent' => $attributes['user_agent'] ?? null,
      'submitted_at' => $attributes['submitted_at'] ?? now(),
    ]);

    return $submission->load('status')->toRecordArray();
  }

  /**
   * @param  array<string, mixed>  $attributes
   * @return array<string, mixed>|null
   */
  public function update(int $formId, int $submissionId, array $attributes): ?array
  {
    $submission = FormSubmission::query()
      ->where('form_id', $formId)
      ->whereKey($submissionId)
      ->first();

    if ($submission === null) {
      return null;
    }

    $submission->update([
      'form_submission_status_id' => $attributes['form_submission_status_id'] ?? $submission->form_submission_status_id,
      'landing_page_id' => array_key_exists('landing_page_id', $attributes)
        ? $attributes['landing_page_id']
        : $submission->landing_page_id,
      'landing_page_slug' => array_key_exists('landing_page_slug', $attributes)
        ? $attributes['landing_page_slug']
        : $submission->landing_page_slug,
      'landing_page_name' => array_key_exists('landing_page_name', $attributes)
        ? $attributes['landing_page_name']
        : $submission->landing_page_name,
      'data' => array_key_exists('data', $attributes) ? $attributes['data'] : $submission->data,
      'ip_address' => array_key_exists('ip_address', $attributes)
        ? $attributes['ip_address']
        : $submission->ip_address,
      'user_agent' => array_key_exists('user_agent', $attributes)
        ? $attributes['user_agent']
        : $submission->user_agent,
      'submitted_at' => array_key_exists('submitted_at', $attributes)
        ? $attributes['submitted_at']
        : $submission->submitted_at,
    ]);

    return $submission->fresh('status')?->toRecordArray();
  }

  /**
   * @param  array<string, mixed>  $submission
   *
   * @deprecated Prefer create()/update() which return the persisted record with the real DB id.
   */
  public function save(int $formId, array $submission): void
  {
    $submissionId = (int) ($submission['id'] ?? 0);

    if ($submissionId > 0 && $this->find($formId, $submissionId) !== null) {
      $this->update($formId, $submissionId, $submission);

      return;
    }

    $this->create(array_merge($submission, ['form_id' => $formId]));
  }

  public function updateStatus(int $formId, int $submissionId, int $statusId): bool
  {
    return FormSubmission::query()
      ->where('form_id', $formId)
      ->whereKey($submissionId)
      ->update(['form_submission_status_id' => $statusId]) > 0;
  }
}
