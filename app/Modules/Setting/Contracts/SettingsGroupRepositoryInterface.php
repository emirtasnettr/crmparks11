<?php

namespace App\Modules\Setting\Contracts;

interface SettingsGroupRepositoryInterface
{
  /**
   * @return array<string, mixed>
   */
  public function get(string $group): array;

  /**
   * @param  array<string, mixed>  $data
   */
  public function put(string $group, array $data): void;

  public function forget(string $group): void;
}
