<?php

namespace App\Modules\Setting\Repositories;

use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Models\Setting;

class DatabaseSettingsGroupRepository implements SettingsGroupRepositoryInterface
{
  public function get(string $group): array
  {
    return Setting::query()
      ->where('group', $group)
      ->get()
      ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->value])
      ->all();
  }

  public function put(string $group, array $data): void
  {
    foreach ($data as $key => $value) {
      Setting::query()->updateOrCreate(
        ['group' => $group, 'key' => $key],
        [
          'value' => $value,
          'type' => $this->resolveType($value),
        ],
      );
    }
  }

  public function forget(string $group): void
  {
    Setting::query()->where('group', $group)->delete();
  }

  private function resolveType(mixed $value): string
  {
    return match (true) {
      is_bool($value) => 'boolean',
      is_int($value) => 'integer',
      is_float($value) => 'float',
      is_array($value) => 'json',
      default => 'string',
    };
  }
}
