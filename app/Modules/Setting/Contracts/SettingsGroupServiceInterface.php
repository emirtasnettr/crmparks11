<?php

namespace App\Modules\Setting\Contracts;

interface SettingsGroupServiceInterface
{
  public function key(): string;

  /**
   * @return array<string, mixed>
   */
  public function defaults(): array;

  /**
   * @return array<string, mixed>
   */
  public function all(): array;

  /**
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  public function save(array $data): array;

  /**
   * @return array<string, mixed>
   */
  public function reset(): array;

  /**
   * @return array<string, string|array>
   */
  public function rules(): array;
}
