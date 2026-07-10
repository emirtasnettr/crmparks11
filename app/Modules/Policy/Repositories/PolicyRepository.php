<?php

namespace App\Modules\Policy\Repositories;

use App\Modules\Policy\Data\PolicyDefinitions;
use App\Modules\Policy\Models\Policy;

class PolicyRepository
{
  /**
   * @return array<string, array<string, mixed>>
   */
  public function all(): array
  {
    $stored = Policy::query()
      ->get()
      ->mapWithKeys(fn (Policy $policy) => [$policy->key => $policy->toRecordArray()])
      ->all();

    return array_merge($this->defaults(), $stored);
  }

  /**
   * @return array<string, mixed>|null
   */
  public function find(string $key): ?array
  {
    $policies = $this->all();

    return $policies[$key] ?? null;
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findBySlug(string $slug): ?array
  {
    $definition = PolicyDefinitions::findBySlug($slug);

    if ($definition === null) {
      return null;
    }

    $policy = $this->find($definition['key']);

    if ($policy === null) {
      return null;
    }

    return array_merge($definition, $policy);
  }

  /**
   * @param  array<string, array<string, mixed>>  $policies
   */
  public function write(array $policies): void
  {
    foreach ($policies as $key => $policy) {
      Policy::query()->updateOrCreate(
        ['key' => $key],
        [
          'slug' => $policy['slug'] ?? PolicyDefinitions::all()[$key]['slug'],
          'title' => $policy['title'] ?? PolicyDefinitions::all()[$key]['title'],
          'content' => $policy['content'] ?? '',
          'meta_title' => $policy['meta_title'] ?? PolicyDefinitions::all()[$key]['title'],
          'meta_description' => $policy['meta_description'] ?? '',
          'updated_at' => $policy['updated_at'] ?? now(),
        ],
      );
    }
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function defaults(): array
  {
    $now = now()->toDateTimeString();
    $defaults = [];

    foreach (PolicyDefinitions::all() as $key => $definition) {
      $defaults[$key] = [
        'key' => $key,
        'slug' => $definition['slug'],
        'title' => $definition['title'],
        'content' => '',
        'meta_title' => $definition['title'],
        'meta_description' => '',
        'updated_at' => $now,
      ];
    }

    return $defaults;
  }
}
