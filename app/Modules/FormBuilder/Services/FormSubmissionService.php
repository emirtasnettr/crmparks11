<?php

namespace App\Modules\FormBuilder\Services;

use App\Modules\FormBuilder\Models\FormSubmissionNote;
use App\Modules\FormBuilder\Repositories\FormBuilderRepository;
use App\Modules\FormBuilder\Repositories\FormSubmissionRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormSubmissionService
{
  public function __construct(
    private readonly FormSubmissionRepository $repository,
    private readonly FormBuilderRepository $formRepository,
    private readonly FormSubmissionMediaService $media,
  ) {}

  /**
   * @param  array<string, mixed>  $filters
   * @return array<int, array<string, mixed>>
   */
  public function listForForm(int $formId, array $filters = []): array
  {
    $form = $this->formRepository->find($formId);

    if ($form === null) {
      abort(404);
    }

    return collect($this->repository->all($formId))
      ->map(fn (array $submission) => $this->enrich($submission, $form))
      ->filter(function (array $submission) use ($filters) {
        if (! empty($filters['search'])) {
          $search = mb_strtolower($filters['search']);
          $haystack = mb_strtolower(implode(' ', array_merge(
            [$submission['landing_page_slug'] ?? ''],
            array_values($submission['data'] ?? [])
          )));

          if (! str_contains($haystack, $search)) {
            return false;
          }
        }

        if (! empty($filters['date_from'])) {
          if (Carbon::parse($submission['submitted_at'])->lt(Carbon::parse($filters['date_from'])->startOfDay())) {
            return false;
          }
        }

        if (! empty($filters['date_to'])) {
          if (Carbon::parse($submission['submitted_at'])->gt(Carbon::parse($filters['date_to'])->endOfDay())) {
            return false;
          }
        }

        return true;
      })
      ->sortByDesc('submitted_at')
      ->values()
      ->all();
  }

  public function countForForm(int $formId): int
  {
    return $this->repository->count($formId);
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findForForm(int $formId, int $submissionId): ?array
  {
    $form = $this->formRepository->find($formId);

    if ($form === null) {
      return null;
    }

    $submission = $this->repository->find($formId, $submissionId);

    return $submission ? $this->enrich($submission, $form) : null;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function notesForSubmission(int $submissionId): array
  {
    return FormSubmissionNote::query()
      ->with('user:id,name')
      ->where('form_submission_id', $submissionId)
      ->latest()
      ->get()
      ->map(fn (FormSubmissionNote $note) => $note->toRecordArray())
      ->all();
  }

  /**
   * @return array<string, mixed>
   */
  public function addNote(int $formId, int $submissionId, string $body, ?int $userId): array
  {
    $submission = $this->repository->find($formId, $submissionId);

    if ($submission === null) {
      abort(404);
    }

    $note = FormSubmissionNote::query()->create([
      'form_submission_id' => $submissionId,
      'user_id' => $userId,
      'body' => trim($body),
    ]);

    $note->load('user:id,name');

    return $note->toRecordArray();
  }

  /**
   * @return array<string, mixed>
   */
  public function storeFromLanding(Request $request, array $form, array $landingPage): array
  {
    if (($form['status'] ?? '') !== 'active') {
      abort(404);
    }

    $fields = collect($form['fields'] ?? [])->filter(fn (array $field) => ($field['type'] ?? '') !== 'heading')->values();
    $rules = $this->buildValidationRules($fields->all());
    $messages = $this->buildValidationMessages($fields->all());

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      throw new ValidationException($validator);
    }

    $validated = $validator->validated();
    $formId = (int) $form['id'];
    $submissionId = $this->repository->nextId($formId);
    $data = [];

    foreach ($fields as $field) {
      $name = $field['name'];

      if (($field['type'] ?? '') === 'file') {
        $uploaded = $request->file($name);

        if ($uploaded !== null && $uploaded->isValid()) {
          $stored = $this->media->store($uploaded, $formId, $submissionId, $name);
          $data[$name] = $stored['original_name'];
          $data[$name.'_url'] = $stored['url'];
        } else {
          $data[$name] = null;
        }

        continue;
      }

      if (($field['type'] ?? '') === 'checkbox') {
        $data[$name] = $request->boolean($name) ? 'Evet' : 'Hayır';

        continue;
      }

      $data[$name] = $validated[$name] ?? null;
    }

    $submission = [
      'id' => $submissionId,
      'form_id' => $formId,
      'landing_page_id' => $landingPage['id'] ?? null,
      'landing_page_slug' => $landingPage['slug'] ?? null,
      'landing_page_name' => $landingPage['name'] ?? null,
      'data' => $data,
      'ip_address' => $request->ip(),
      'user_agent' => (string) $request->userAgent(),
      'submitted_at' => Carbon::now()->toDateTimeString(),
    ];

    $this->repository->save($formId, $submission);

    return $this->enrich($submission, $form);
  }

  /**
   * @param  array<int, array<string, mixed>>  $fields
   * @return array<string, mixed>
   */
  public function buildValidationRules(array $fields): array
  {
    $rules = [];

    foreach ($fields as $field) {
      if (($field['type'] ?? '') === 'heading') {
        continue;
      }

      $name = $field['name'];

      $fieldRules = match ($field['type'] ?? 'text') {
        'checkbox' => [($field['required'] ?? false) ? 'accepted' : 'nullable'],
        default => [($field['required'] ?? false) ? 'required' : 'nullable'],
      };

      if (($field['type'] ?? 'text') !== 'checkbox') {
        $fieldRules = array_merge($fieldRules, match ($field['type'] ?? 'text') {
          'email' => ['email', 'max:255'],
          'number' => ['numeric'],
          'date' => ['date'],
          'file' => ['file', 'max:5120', 'mimes:pdf,png,jpg,jpeg,webp'],
          'select', 'radio' => ['string', 'max:255'],
          'textarea' => ['string', 'max:5000'],
          'phone' => ['string', 'max:50'],
          default => ['string', 'max:2000'],
        });
      }

      if (in_array($field['type'] ?? '', ['select', 'radio'], true)) {
        $options = array_values(array_filter((array) ($field['options'] ?? [])));

        if ($options !== []) {
          $fieldRules[] = Rule::in($options);
        }
      }

      $rules[$name] = $fieldRules;
    }

    return $rules;
  }

  /**
   * @param  array<int, array<string, mixed>>  $fields
   * @return array<string, string>
   */
  public function buildValidationMessages(array $fields): array
  {
    $messages = [];

    foreach ($fields as $field) {
      if (($field['type'] ?? '') === 'heading') {
        continue;
      }

      $messages[$field['name'].'.required'] = ($field['label'] ?? 'Alan').' zorunludur.';
    }

    return $messages;
  }

  /**
   * @param  array<string, mixed>  $form
   * @return array<int, array{name: string, label: string, type: string}>
   */
  public function exportableFields(array $form): array
  {
    return collect($form['fields'] ?? [])
      ->filter(fn (array $field) => ($field['type'] ?? '') !== 'heading')
      ->map(fn (array $field) => [
        'name' => $field['name'],
        'label' => $field['label'],
        'type' => $field['type'],
      ])
      ->values()
      ->all();
  }

  /**
   * @param  array<string, mixed>  $submission
   * @param  array<string, mixed>  $form
   * @return array<string, mixed>
   */
  public function enrich(array $submission, array $form): array
  {
    $fieldMap = collect($form['fields'] ?? [])
      ->filter(fn (array $field) => ($field['type'] ?? '') !== 'heading')
      ->keyBy('name');

    $data = $submission['data'] ?? [];

    $values = collect($data)->map(function ($value, $key) use ($fieldMap, $data) {
      if (str_ends_with((string) $key, '_url')) {
        return null;
      }

      $field = $fieldMap->get($key);

      return [
        'name' => $key,
        'label' => $field['label'] ?? $key,
        'type' => $field['type'] ?? 'text',
        'value' => is_array($value) ? implode(', ', $value) : (string) ($value ?? ''),
        'url' => $data[$key.'_url'] ?? null,
      ];
    })->filter()->all();

    return array_merge($submission, [
      'values' => $values,
      'submitted_at_formatted' => Carbon::parse($submission['submitted_at'] ?? now())->format('d.m.Y H:i'),
    ]);
  }
}
