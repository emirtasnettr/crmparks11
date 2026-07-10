<?php

namespace App\Modules\LandingPage\Repositories;

use App\Modules\LandingPage\Models\LandingPage;

class LandingPageRepository
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(): array
  {
    return LandingPage::query()
      ->orderBy('id')
      ->get()
      ->map(fn (LandingPage $page) => $page->toRecordArray())
      ->all();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    $page = LandingPage::query()->find($id);

    return $page?->toRecordArray();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findBySlug(string $slug): ?array
  {
    $page = LandingPage::query()->where('slug', $slug)->first();

    return $page?->toRecordArray();
  }

  /**
   * @param  array<string, mixed>  $page
   */
  public function save(array $page): void
  {
    LandingPage::query()->updateOrCreate(
      ['id' => (int) $page['id']],
      [
        'uuid' => $page['uuid'],
        'name' => $page['name'],
        'slug' => $page['slug'],
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
  }

  public function delete(int $id): bool
  {
    return (bool) LandingPage::query()->whereKey($id)->delete();
  }

  public function nextId(): int
  {
    return (int) (LandingPage::query()->max('id') ?? 0) + 1;
  }
}
