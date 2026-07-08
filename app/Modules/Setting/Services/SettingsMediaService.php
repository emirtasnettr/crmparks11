<?php

namespace App\Modules\Setting\Services;

use App\Support\PublicMediaUrl;
use App\Support\SecureUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsMediaService
{
  private const DISK = 'public';

  private const PATH_PREFIX = 'settings/media';

  /**
   * @return array{path: string, url: string}
   */
  public function store(UploadedFile $file, string $group, string $field): array
  {
    $profile = SecureUploadValidator::imageProfile();
    $extension = SecureUploadValidator::assertAllowed($file, $profile['extensions'], $profile['mimeTypes']);

    $filename = Str::slug($group.'-'.$field).'-'.time().'.'.$extension;
    $path = $file->storeAs(self::PATH_PREFIX.'/'.$group, $filename, self::DISK);

    return [
      'path' => $path,
      'url' => $this->url($path),
    ];
  }

  public function url(?string $path): ?string
  {
    if ($path === null || $path === '') {
      return null;
    }

    return PublicMediaUrl::fromPath($path);
  }
}
