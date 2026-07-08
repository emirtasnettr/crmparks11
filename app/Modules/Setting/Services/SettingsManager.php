<?php

namespace App\Modules\Setting\Services;

use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Contracts\SettingsGroupServiceInterface;
use App\Modules\Setting\Data\SettingsCategoryRegistry;
use InvalidArgumentException;

class SettingsManager
{
  /** @var array<string, SettingsGroupServiceInterface> */
  private array $services;

  public function __construct(SettingsGroupRepositoryInterface $repository)
  {
    $this->services = SettingsGroupServices::registry($repository);
  }

  /**
   * @return array<string, array{label: string, icon: string, description: string}>
   */
  public function categories(): array
  {
    return SettingsCategoryRegistry::all();
  }

  public function group(string $key): SettingsGroupServiceInterface
  {
    if (! isset($this->services[$key])) {
      throw new InvalidArgumentException("Bilinmeyen ayar grubu: {$key}");
    }

    return $this->services[$key];
  }

  public function resolveSection(?string $section): string
  {
    $section = $section ?: 'general';

    return SettingsCategoryRegistry::isValid($section) ? $section : 'general';
  }
}
