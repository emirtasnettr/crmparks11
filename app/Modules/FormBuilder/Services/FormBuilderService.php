<?php

namespace App\Modules\FormBuilder\Services;

use App\Modules\FormBuilder\Data\FormFieldTypes;
use App\Modules\FormBuilder\Repositories\FormBuilderRepository;
use App\Modules\FormBuilder\Repositories\FormSubmissionRepository;
use App\Modules\User\Data\UserManagementFormData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormBuilderService
{
  public function __construct(
    private readonly FormBuilderRepository $repository,
    private readonly FormSubmissionRepository $submissionRepository,
  ) {}

  /**
   * @param  array<string, mixed>  $filters
   * @return array<int, array<string, mixed>>
   */
  public function list(array $filters = []): array
  {
    return collect($this->repository->all())
      ->map(fn (array $form) => $this->enrich($form))
      ->filter(function (array $form) use ($filters) {
        if (! empty($filters['search'])) {
          $search = mb_strtolower($filters['search']);
          $haystack = mb_strtolower(implode(' ', [$form['name'], $form['description'] ?? '']));

          if (! str_contains($haystack, $search)) {
            return false;
          }
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
          if ($form['status'] !== $filters['status']) {
            return false;
          }
        }

        return true;
      })
      ->sortByDesc('updated_at')
      ->values()
      ->all();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    $form = $this->repository->find($id);

    return $form ? $this->enrich($form) : null;
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function create(array $data): array
  {
    $validated = $this->validateMeta($data);
    $now = Carbon::now()->toDateTimeString();

    $form = [
      'id' => $this->repository->nextId(),
      'uuid' => 'frm-'.Str::lower(Str::random(8)),
      'name' => $validated['name'],
      'slug' => $this->uniqueSlug($validated['name']),
      'description' => $validated['description'] ?? '',
      'status' => $validated['status'] ?? 'draft',
      'fields' => [],
      'notify_user_ids' => $validated['notify_user_ids'],
      'notify_roles' => $validated['notify_roles'],
      'created_at' => $now,
      'updated_at' => $now,
    ];

    $this->repository->save($form);

    return $this->enrich($form);
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function update(int $id, array $data): array
  {
    $form = $this->repository->find($id);

    if ($form === null) {
      abort(404);
    }

    $validated = $this->validateMeta($data, $id);
    $fields = $this->decodeFields($data['fields_json'] ?? '[]');

    $form['name'] = $validated['name'];
    $form['description'] = $validated['description'] ?? '';
    $form['status'] = $validated['status'] ?? $form['status'];
    $form['fields'] = $fields;
    $form['notify_user_ids'] = $validated['notify_user_ids'];
    $form['notify_roles'] = $validated['notify_roles'];
    $form['updated_at'] = Carbon::now()->toDateTimeString();

    if (($validated['slug'] ?? null) && $validated['slug'] !== $form['slug']) {
      $form['slug'] = $this->uniqueSlug($validated['slug'], $id);
    }

    $this->repository->save($form);

    return $this->enrich($form);
  }

  public function delete(int $id): void
  {
    if (! $this->repository->delete($id)) {
      abort(404);
    }
  }

  /**
   * @param  array<string, mixed>  $form
   * @return array<string, mixed>
   */
  public function enrich(array $form): array
  {
    $fields = $form['fields'] ?? [];

    return array_merge($form, [
      'field_count' => count($fields),
      'submission_count' => $this->submissionRepository->count((int) $form['id']),
      'status_label' => match ($form['status'] ?? 'draft') {
        'active' => 'Yayında',
        'archived' => 'Arşiv',
        default => 'Taslak',
      },
      'updated_at_formatted' => Carbon::parse($form['updated_at'] ?? now())->format('d.m.Y H:i'),
      'created_at_formatted' => Carbon::parse($form['created_at'] ?? now())->format('d.m.Y H:i'),
    ]);
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  private function validateMeta(array $data, ?int $ignoreId = null): array
  {
    $allowedRoles = array_keys(UserManagementFormData::roleLabels());

    $validator = Validator::make($data, [
      'name' => 'required|string|max:120',
      'description' => 'nullable|string|max:500',
      'status' => 'nullable|in:draft,active,archived',
      'slug' => 'nullable|string|max:120',
      'notify_user_ids' => 'nullable|array',
      'notify_user_ids.*' => ['integer', Rule::exists('users', 'id')],
      'notify_roles' => 'nullable|array',
      'notify_roles.*' => ['string', Rule::in($allowedRoles), Rule::exists('roles', 'name')],
    ], [
      'name.required' => 'Form adı zorunludur.',
      'notify_user_ids.*.exists' => 'Seçilen bildirim kullanıcısı geçersiz.',
      'notify_roles.*.in' => 'Seçilen bildirim rolü geçersiz.',
      'notify_roles.*.exists' => 'Seçilen bildirim rolü bulunamadı.',
    ]);

    if ($validator->fails()) {
      throw new ValidationException($validator);
    }

    $validated = $validator->validated();
    $validated['notify_user_ids'] = array_values(array_unique(array_map(
      'intval',
      $validated['notify_user_ids'] ?? []
    )));
    $validated['notify_roles'] = array_values(array_unique(array_map(
      'strval',
      $validated['notify_roles'] ?? []
    )));

    return $validated;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function decodeFields(string $json): array
  {
    $fields = json_decode($json, true);

    if (! is_array($fields)) {
      throw ValidationException::withMessages([
        'fields_json' => 'Form alanları geçersiz.',
      ]);
    }

    return collect($fields)
      ->filter(fn ($field) => is_array($field) && ! empty($field['type']))
      ->map(function (array $field, int $index) {
        $type = $field['type'];
        if (! isset(FormFieldTypes::palette()[$type])) {
          throw ValidationException::withMessages([
            'fields_json' => 'Geçersiz alan tipi: '.$type,
          ]);
        }

        $normalized = [
          'id' => $field['id'] ?? 'field_'.($index + 1),
          'type' => $type,
          'label' => trim((string) ($field['label'] ?? 'Alan')),
          'name' => Str::slug((string) ($field['name'] ?? 'alan_'.$index), '_'),
          'placeholder' => (string) ($field['placeholder'] ?? ''),
          'help_text' => (string) ($field['help_text'] ?? ''),
          'required' => (bool) ($field['required'] ?? false),
          'width' => in_array($field['width'] ?? 'full', ['full', 'half'], true) ? $field['width'] : 'full',
          'options' => array_values(array_filter((array) ($field['options'] ?? []))),
        ];

        if ($type === 'heading') {
          $normalized['required'] = false;
        }

        return $normalized;
      })
      ->values()
      ->all();
  }

  private function uniqueSlug(string $value, ?int $ignoreId = null): string
  {
    $base = Str::slug($value);
    $slug = $base;
    $counter = 2;

    while ($this->slugExists($slug, $ignoreId)) {
      $slug = $base.'-'.$counter;
      $counter++;
    }

    return $slug;
  }

  private function slugExists(string $slug, ?int $ignoreId = null): bool
  {
    return collect($this->repository->all())->contains(function (array $form) use ($slug, $ignoreId) {
      if ($ignoreId !== null && (int) $form['id'] === $ignoreId) {
        return false;
      }

      return ($form['slug'] ?? '') === $slug;
    });
  }
}
