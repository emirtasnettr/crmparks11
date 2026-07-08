<?php

namespace App\Modules\LandingPage\Services;

use App\Modules\FormBuilder\Repositories\FormBuilderRepository;
use App\Modules\LandingPage\Repositories\LandingPageRepository;
use App\Modules\LandingPage\Support\LandingPageContentSanitizer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LandingPageService
{
  public function __construct(
    private readonly LandingPageRepository $repository,
    private readonly FormBuilderRepository $formRepository,
    private readonly LandingPageMediaService $media,
  ) {}

  /**
   * @param  array<string, mixed>  $filters
   * @return array<int, array<string, mixed>>
   */
  public function list(array $filters = []): array
  {
    return collect($this->repository->all())
      ->map(fn (array $page) => $this->enrich($page))
      ->filter(function (array $page) use ($filters) {
        if (! empty($filters['search'])) {
          $search = mb_strtolower($filters['search']);
          $haystack = mb_strtolower(implode(' ', [$page['name'], $page['title'], $page['slug']]));

          if (! str_contains($haystack, $search)) {
            return false;
          }
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
          if ($page['status'] !== $filters['status']) {
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
   * @return array<int, array{id: int, name: string}>
   */
  public function formOptions(): array
  {
    return collect($this->formRepository->all())
      ->map(fn (array $form) => [
        'id' => (int) $form['id'],
        'name' => $form['name'],
        'status' => $form['status'] ?? 'draft',
        'fields' => $form['fields'] ?? [],
      ])
      ->values()
      ->all();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    $page = $this->repository->find($id);

    return $page ? $this->enrich($page) : null;
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findPublicBySlug(string $slug): ?array
  {
    $page = $this->repository->findBySlug($slug);

    if ($page === null || ($page['status'] ?? '') !== 'active') {
      return null;
    }

    return $this->enrich($page, true);
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function create(array $data): array
  {
    $validated = $this->validateMeta($data);
    $now = Carbon::now()->toDateTimeString();

    $page = [
      'id' => $this->repository->nextId(),
      'uuid' => 'lp-'.Str::lower(Str::random(8)),
      'name' => $validated['name'],
      'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['name']),
      'status' => $validated['status'] ?? 'draft',
      'hero_image_path' => null,
      'title' => $validated['title'] ?? '',
      'content' => LandingPageContentSanitizer::clean($validated['content'] ?? ''),
      'form_id' => isset($validated['form_id']) ? (int) $validated['form_id'] : null,
      'meta_title' => $validated['meta_title'] ?? '',
      'meta_description' => $validated['meta_description'] ?? '',
      'created_at' => $now,
      'updated_at' => $now,
    ];

    $this->repository->save($page);

    return $this->enrich($page);
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function update(int $id, array $data, ?\Illuminate\Http\UploadedFile $heroImage = null): array
  {
    $page = $this->repository->find($id);

    if ($page === null) {
      abort(404);
    }

    $validated = $this->validateMeta($data, $id);

    if ($heroImage !== null && $heroImage->isValid()) {
      $stored = $this->media->store($heroImage, $id);
      $page['hero_image_path'] = $stored['path'];
    }

    $page['name'] = $validated['name'];
    $page['slug'] = $this->uniqueSlug($validated['slug'] ?? $page['slug'], $id);
    $page['status'] = $validated['status'] ?? $page['status'];
    $page['title'] = $validated['title'] ?? '';
    $page['content'] = LandingPageContentSanitizer::clean($validated['content'] ?? '');
    $page['form_id'] = ! empty($validated['form_id']) ? (int) $validated['form_id'] : null;
    $page['meta_title'] = $validated['meta_title'] ?? '';
    $page['meta_description'] = $validated['meta_description'] ?? '';
    $page['updated_at'] = Carbon::now()->toDateTimeString();

    $this->repository->save($page);

    return $this->enrich($page);
  }

  public function delete(int $id): void
  {
    if (! $this->repository->delete($id)) {
      abort(404);
    }
  }

  /**
   * @param  array<string, mixed>  $page
   * @return array<string, mixed>
   */
  public function enrich(array $page, bool $public = false): array
  {
    $form = ! empty($page['form_id']) ? $this->formRepository->find((int) $page['form_id']) : null;

    $enriched = array_merge($page, [
      'hero_image_url' => $this->media->url($page['hero_image_path'] ?? null),
      'form_name' => $form['name'] ?? null,
      'form_fields' => $form['fields'] ?? [],
      'status_label' => match ($page['status'] ?? 'draft') {
        'active' => 'Yayında',
        'archived' => 'Arşiv',
        default => 'Taslak',
      },
      'updated_at_formatted' => Carbon::parse($page['updated_at'] ?? now())->format('d.m.Y H:i'),
      'public_url' => ! empty($page['slug']) ? route('landing.show', $page['slug']) : null,
    ]);

    if (! $public) {
      return $enriched;
    }

    return $enriched;
  }

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  private function validateMeta(array $data, ?int $ignoreId = null): array
  {
    $validator = Validator::make($data, [
      'name' => 'required|string|max:120',
      'slug' => 'nullable|string|max:120',
      'status' => 'nullable|in:draft,active,archived',
      'title' => 'nullable|string|max:255',
      'content' => 'nullable|string|max:20000',
      'form_id' => 'nullable|integer',
      'meta_title' => 'nullable|string|max:70',
      'meta_description' => 'nullable|string|max:160',
    ], [
      'name.required' => 'Landing page adı zorunludur.',
    ]);

    if ($validator->fails()) {
      throw new ValidationException($validator);
    }

    $validated = $validator->validated();

    if (! empty($validated['form_id'])) {
      $form = $this->formRepository->find((int) $validated['form_id']);

      if ($form === null) {
        throw ValidationException::withMessages([
          'form_id' => 'Seçilen form bulunamadı.',
        ]);
      }
    }

    return $validated;
  }

  private function uniqueSlug(string $value, ?int $ignoreId = null): string
  {
    $base = Str::slug($value);
    $slug = $base ?: 'landing-page';
    $counter = 2;

    while ($this->slugExists($slug, $ignoreId)) {
      $slug = $base.'-'.$counter;
      $counter++;
    }

    return $slug;
  }

  private function slugExists(string $slug, ?int $ignoreId = null): bool
  {
    return collect($this->repository->all())->contains(function (array $page) use ($slug, $ignoreId) {
      if ($ignoreId !== null && (int) $page['id'] === $ignoreId) {
        return false;
      }

      return ($page['slug'] ?? '') === $slug;
    });
  }
}
