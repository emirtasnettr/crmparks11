<?php

namespace App\Modules\Setting\Repositories;

use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class FileSettingsGroupRepository implements SettingsGroupRepositoryInterface
{
  private const DISK = 'local';

  private const PATH_PREFIX = 'settings/groups';

  public function get(string $group): array
  {
    $path = $this->path($group);

    if (! Storage::disk(self::DISK)->exists($path)) {
      return [];
    }

    $contents = Storage::disk(self::DISK)->get($path);
    $decoded = json_decode($contents, true);

    return is_array($decoded) ? $decoded : [];
  }

  public function put(string $group, array $data): void
  {
    Storage::disk(self::DISK)->put(
      $this->path($group),
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
  }

  public function forget(string $group): void
  {
    $path = $this->path($group);

    if (Storage::disk(self::DISK)->exists($path)) {
      Storage::disk(self::DISK)->delete($path);
    }
  }

  private function path(string $group): string
  {
    return self::PATH_PREFIX.'/'.$group.'.json';
  }
}
