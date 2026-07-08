<?php

namespace App\Modules\LandingPage\Repositories;

use Illuminate\Support\Facades\Storage;

class LandingPageRepository
{
  private const DISK = 'local';

  private const PATH = 'landing-page-builder/pages.json';

  /**
   * @return array<int, array<string, mixed>>
   */
  public function all(): array
  {
    if (! Storage::disk(self::DISK)->exists(self::PATH)) {
      return [];
    }

    $decoded = json_decode(Storage::disk(self::DISK)->get(self::PATH), true);

    return is_array($decoded) ? $decoded : [];
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(int $id): ?array
  {
    return collect($this->all())->firstWhere('id', $id);
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findBySlug(string $slug): ?array
  {
    return collect($this->all())->firstWhere('slug', $slug);
  }

  /**
   * @param  array<string, mixed>  $page
   */
  public function save(array $page): void
  {
    $pages = collect($this->all());
    $index = $pages->search(fn (array $item) => (int) $item['id'] === (int) $page['id']);

    if ($index === false) {
      $pages->push($page);
    } else {
      $pages[$index] = $page;
    }

    $this->write($pages->values()->all());
  }

  public function delete(int $id): bool
  {
    $before = count($this->all());
    $pages = collect($this->all())->reject(fn (array $item) => (int) $item['id'] === $id)->values()->all();
    $this->write($pages);

    return count($pages) < $before;
  }

  public function nextId(): int
  {
    $max = collect($this->all())->max('id');

    return ($max ?? 0) + 1;
  }

  /**
   * @param  array<int, array<string, mixed>>  $pages
   */
  private function write(array $pages): void
  {
    Storage::disk(self::DISK)->put(
      self::PATH,
      json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
  }
}
