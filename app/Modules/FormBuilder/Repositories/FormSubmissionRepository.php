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
   * @param  array<string, mixed>  $submission
   */
  public function save(int $formId, array $submission): void
  {
    FormSubmission::query()->updateOrCreate(
      [
        'id' => (int) $submission['id'],
        'form_id' => $formId,
      ],
      [
        'form_submission_status_id' => $submission['form_submission_status_id'] ?? null,
        'landing_page_id' => $submission['landing_page_id'] ?? null,
        'landing_page_slug' => $submission['landing_page_slug'] ?? null,
        'landing_page_name' => $submission['landing_page_name'] ?? null,
        'data' => $submission['data'] ?? [],
        'ip_address' => $submission['ip_address'] ?? null,
        'user_agent' => $submission['user_agent'] ?? null,
        'submitted_at' => $submission['submitted_at'] ?? now(),
      ],
    );
  }

  public function updateStatus(int $formId, int $submissionId, int $statusId): bool
  {
    return FormSubmission::query()
      ->where('form_id', $formId)
      ->whereKey($submissionId)
      ->update(['form_submission_status_id' => $statusId]) > 0;
  }

  public function nextId(int $formId): int
  {
    $max = FormSubmission::query()->where('form_id', $formId)->max('id');

    return (int) ($max ?? 0) + 1;
  }
}
