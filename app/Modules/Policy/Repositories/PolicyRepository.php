<?php

namespace App\Modules\Policy\Repositories;

use App\Modules\Policy\Data\PolicyDefinitions;
use Illuminate\Support\Facades\Storage;

class PolicyRepository
{
  private const DISK = 'local';

  private const PATH = 'policy-settings/policies.json';

  /**
   * @return array<string, array<string, mixed>>
   */
  public function all(): array
  {
    if (! Storage::disk(self::DISK)->exists(self::PATH)) {
      return $this->defaults();
    }

    $decoded = json_decode(Storage::disk(self::DISK)->get(self::PATH), true);

    if (! is_array($decoded)) {
      return $this->defaults();
    }

    return array_merge($this->defaults(), $decoded);
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
    Storage::disk(self::DISK)->put(
      self::PATH,
      json_encode($policies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
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
